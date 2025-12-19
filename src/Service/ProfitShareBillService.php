<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareBillTask;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOperationLog;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareBillStatus;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOperationType;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareBillTaskRepository;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOperationLogRepository;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareBillDownloadRequest;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareBillRequest;
use Tourze\WechatPayProfitShareBundle\Service\Helper\WechatPayProfitShareHelperTrait;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Service\WechatPayBuilder;
use Yiisoft\Json\Json;

#[WithMonologChannel(channel: 'wechat_pay_profit_share')]
final class ProfitShareBillService
{
    use WechatPayProfitShareHelperTrait;

    public function __construct(
        private readonly ProfitShareBillTaskRepository $billTaskRepository,
        private readonly ProfitShareOperationLogRepository $operationLogRepository,
        private readonly WechatPayBuilder $wechatPayBuilder,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function applyBill(Merchant $merchant, ProfitShareBillRequest $request): ProfitShareBillTask
    {
        $billDateClone = clone $request->getBillDate();
        if ($billDateClone instanceof \DateTime) {
            $billDateClone->setTime(0, 0);
            $billDate = \DateTimeImmutable::createFromMutable($billDateClone);
        } elseif ($billDateClone instanceof \DateTimeImmutable) {
            $billDate = $billDateClone->setTime(0, 0);
        } else {
            throw new \RuntimeException('Unexpected date type');
        }

        $criteria = [
            'subMchId' => $request->getSubMchId(),
            'billDate' => $billDate,
            'tarType' => $request->getTarType(),
        ];

        $task = $this->billTaskRepository->findOneBy($criteria);
        if (null === $task) {
            $task = new ProfitShareBillTask();
            $task->setMerchant($merchant);
            $task->setSubMchId($request->getSubMchId());
            $task->setBillDate($billDate);
            $task->setTarType($request->getTarType());
        }

        $query = $request->toQuery();
        $builder = $this->wechatPayBuilder->genBuilder($merchant);
        $segment = sprintf('v3/profitsharing/bills?%s', http_build_query($query));
        $startTime = microtime(true);

        try {
            $this->logger->info('申请下载分账账单', [
                'sub_mchid' => $request->getSubMchId(),
                'bill_date' => $request->getBillDate()->format('Y-m-d'),
            ]);

            $response = $builder->chain($segment)->get();
            $body = $response->getBody()->getContents();
            /** @var array<string, mixed> $data */
            $data = Json::decode($body);

            $this->logger->info('申请分账账单成功', [
                'sub_mchid' => $request->getSubMchId(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            $hashType = $data['hash_type'] ?? null;
            $task->setHashType(\is_string($hashType) ? $hashType : null);
            $hashValue = $data['hash_value'] ?? null;
            $task->setHashValue(\is_string($hashValue) ? $hashValue : null);
            $downloadUrl = $data['download_url'] ?? null;
            $task->setDownloadUrl(\is_string($downloadUrl) ? $downloadUrl : null);
            $task->setStatus(ProfitShareBillStatus::READY);
            $task->setResponsePayload($this->encodePayload($data));
            $task->setRequestPayload($this->encodePayload($query));
            $this->billTaskRepository->save($task);

            $this->logOperation(
                $merchant,
                $request->getSubMchId(),
                ProfitShareOperationType::APPLY_BILL,
                true,
                null,
                null,
                $query,
                $data,
            );

            return $task;
        } catch (\Throwable $exception) {
            $this->logger->error('申请分账账单失败', [
                'sub_mchid' => $request->getSubMchId(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'exception' => $exception->getMessage(),
            ]);

            $this->logOperation(
                $merchant,
                $request->getSubMchId(),
                ProfitShareOperationType::APPLY_BILL,
                false,
                $this->extractErrorCode($exception),
                $exception->getMessage(),
                $query,
                ['exception' => $exception->getMessage()],
            );

            throw $exception;
        }
    }

    /**
     * @param array<string, mixed>|string|null $payload
     */
    private function encodePayload($payload): ?string
    {
        if (null === $payload || '' === $payload) {
            return null;
        }

        if (\is_string($payload)) {
            return $payload;
        }

        return Json::encode($payload);
    }

    /**
     * @param array<string, mixed> $request
     * @param array<string, mixed>|string|null $response
     */
    private function logOperation(
        Merchant $merchant,
        ?string $subMchId,
        ProfitShareOperationType $type,
        bool $success,
        ?string $errorCode,
        ?string $errorMessage,
        array $request,
        $response,
    ): void {
        $log = new ProfitShareOperationLog();
        $log->setMerchant($merchant);
        $log->setSubMchId($subMchId);
        $log->setType($type);
        $log->setSuccess($success);
        $log->setErrorCode($errorCode);
        $log->setErrorMessage($errorMessage);
        $log->setRequestPayload($this->encodePayload($request));
        $log->setResponsePayload($this->encodePayload($response));

        $this->operationLogRepository->save($log);
    }

    private function extractErrorCode(\Throwable $exception): ?string
    {
        $code = $exception->getCode();

        return 0 === $code ? null : (string) $code;
    }

    public function downloadBill(
        Merchant $merchant,
        ProfitShareBillTask $task,
        ProfitShareBillDownloadRequest $request,
    ): ProfitShareBillTask {
        $downloadUrl = $this->resolveDownloadUrl($request, $task);
        $localPath = $this->validateLocalPath($request);

        $startTime = microtime(true);

        try {
            $this->logger->info('开始下载分账账单', [
                'download_url' => $downloadUrl,
                'local_path' => $localPath,
            ]);

            $contents = $this->fetchBillContent($merchant, $downloadUrl);
            $contents = $this->maybeDecodeGzip($contents, $request, $task);

            $this->prepareLocalFile($localPath, $contents);
            $this->verifyDownloadedHash($contents, $request, $task);

            return $this->finalizeDownload($merchant, $task, $downloadUrl, $localPath, $startTime);
        } catch (\Throwable $exception) {
            $this->handleDownloadError($merchant, $task, $downloadUrl, $localPath, $exception, $startTime);
            throw $exception;
        }
    }

    private function resolveDownloadUrl(
        ProfitShareBillDownloadRequest $request,
        ProfitShareBillTask $task,
    ): string {
        $downloadUrl = null !== $request->getDownloadUrl() && '' !== $request->getDownloadUrl()
            ? $request->getDownloadUrl()
            : $task->getDownloadUrl();

        if (null === $downloadUrl || '' === $downloadUrl) {
            throw new \InvalidArgumentException('无可用的下载地址');
        }

        return $downloadUrl;
    }

    private function validateLocalPath(ProfitShareBillDownloadRequest $request): string
    {
        $localPath = $request->getLocalPath();
        if (null === $localPath) {
            throw new \InvalidArgumentException('本地保存路径不能为空');
        }

        return $localPath;
    }

    private function fetchBillContent(Merchant $merchant, string $downloadUrl): string
    {
        $builder = $this->wechatPayBuilder->genBuilder($merchant);
        $path = $this->buildSegmentFromUrl($downloadUrl);

        $response = $builder->chain($path)->get([
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        return $response->getBody()->getContents();
    }

    private function maybeDecodeGzip(
        string $contents,
        ProfitShareBillDownloadRequest $request,
        ProfitShareBillTask $task,
    ): string {
        $tarType = $request->getTarType() ?? $task->getTarType();
        if (null !== $tarType && 'GZIP' === strtoupper($tarType)) {
            $decoded = gzdecode($contents);
            if (false === $decoded) {
                throw new \RuntimeException('账单文件解压失败');
            }

            return $decoded;
        }

        return $contents;
    }

    private function prepareLocalFile(string $localPath, string $contents): void
    {
        $this->dumpToFile($localPath, $contents);
    }

    /**
     * @return array{type: ?string, value: ?string}
     */
    private function resolveHashExpectation(
        ProfitShareBillDownloadRequest $request,
        ProfitShareBillTask $task,
    ): array {
        return [
            'type' => $request->getExpectedHashType() ?? $task->getHashType(),
            'value' => $request->getExpectedHashValue() ?? $task->getHashValue(),
        ];
    }

    private function verifyDownloadedHash(
        string $contents,
        ProfitShareBillDownloadRequest $request,
        ProfitShareBillTask $task,
    ): void {
        [$expectedHashType, $expectedHashValue] = array_values($this->resolveHashExpectation($request, $task));

        if (null !== $expectedHashType && null !== $expectedHashValue) {
            $this->verifyHash($expectedHashType, $expectedHashValue, $contents);
        }
    }

    private function finalizeDownload(
        Merchant $merchant,
        ProfitShareBillTask $task,
        string $downloadUrl,
        string $localPath,
        float $startTime,
    ): ProfitShareBillTask {
        $task->setStatus(ProfitShareBillStatus::DOWNLOADED);
        $task->setDownloadedAt(new \DateTimeImmutable());
        $task->setLocalPath($localPath);
        $task->setResponsePayload($this->encodePayload([
            'download_url' => $downloadUrl,
            'local_path' => $localPath,
        ]));
        $this->billTaskRepository->save($task);

        $this->logOperation(
            $merchant,
            $task->getSubMchId(),
            ProfitShareOperationType::DOWNLOAD_BILL,
            true,
            null,
            null,
            [
                'download_url' => $downloadUrl,
                'local_path' => $localPath,
            ],
            ['status' => 'downloaded'],
        );

        $this->logger->info('下载分账账单成功', [
            'local_path' => $localPath,
            'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
        ]);

        return $task;
    }

    private function handleDownloadError(
        Merchant $merchant,
        ProfitShareBillTask $task,
        string $downloadUrl,
        string $localPath,
        \Throwable $exception,
        float $startTime,
    ): void {
        $this->logger->error('下载分账账单失败', [
            'download_url' => $downloadUrl,
            'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            'exception' => $exception->getMessage(),
        ]);

        $task->setStatus(ProfitShareBillStatus::FAILED);
        $this->billTaskRepository->save($task);

        $this->logOperation(
            $merchant,
            $task->getSubMchId(),
            ProfitShareOperationType::DOWNLOAD_BILL,
            false,
            $this->extractErrorCode($exception),
            $exception->getMessage(),
            [
                'download_url' => $downloadUrl,
                'local_path' => $localPath,
            ],
            ['exception' => $exception->getMessage()],
        );
    }

    private function buildSegmentFromUrl(string $url): string
    {
        $parsed = parse_url($url);
        if (false === $parsed || !isset($parsed['path'])) {
            throw new \InvalidArgumentException('无效的下载地址');
        }

        $segment = ltrim($parsed['path'], '/');
        if (isset($parsed['query']) && '' !== $parsed['query']) {
            $segment .= '?' . $parsed['query'];
        }

        return $segment;
    }

    private function dumpToFile(string $path, string $content): void
    {
        $directory = \dirname($path);
        if (!is_dir($directory) && !@mkdir($directory, 0o777, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('无法创建目录：%s', $directory));
        }

        if (false === file_put_contents($path, $content)) {
            throw new \RuntimeException(sprintf('写入账单文件失败：%s', $path));
        }
    }

    private function verifyHash(string $expectedType, string $expectedValue, string $content): void
    {
        $hashType = strtoupper($expectedType);

        if ('SHA1' !== $hashType) {
            $this->logger->warning('未实现的账单校验算法', [
                'hash_type' => $hashType,
            ]);

            return;
        }

        $calculated = sha1($content);
        if (0 !== strcasecmp($calculated, $expectedValue)) {
            throw new \RuntimeException('账单文件校验失败：哈希值不匹配');
        }
    }
}
