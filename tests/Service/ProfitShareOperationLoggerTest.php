<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOperationLog;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOperationType;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOperationLogRepository;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareOperationLogger;
use WechatPayBundle\Entity\Merchant;

/**
 * @internal
 */
#[CoversClass(ProfitShareOperationLogger::class)]
class ProfitShareOperationLoggerTest extends TestCase
{
    private ProfitShareOperationLogger $logger;

    /** @phpstan-var MockObject&ProfitShareOperationLogRepository */
    private ProfitShareOperationLogRepository $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProfitShareOperationLogRepository::class);
        $this->logger = new ProfitShareOperationLogger($this->repository);
    }

    public function testLogOperationWithSuccessfulRequest(): void
    {
        $merchant = $this->createMock(Merchant::class);
        $subMchId = 'test_sub_mch_id';
        $type = ProfitShareOperationType::REQUEST_ORDER;
        $success = true;
        $errorCode = null;
        $errorMessage = null;
        $request = ['key' => 'value'];
        $response = ['result' => 'success'];

        $this->repository->expects($this->once())
            ->method('save')
            ->with(self::callback(function (ProfitShareOperationLog $log) use ($merchant, $subMchId, $type, $success, $errorCode, $errorMessage, $request, $response) {
                return $log->getMerchant() === $merchant
                    && $log->getSubMchId() === $subMchId
                    && $log->getType() === $type
                    && $log->isSuccess() === $success
                    && $log->getErrorCode() === $errorCode
                    && $log->getErrorMessage() === $errorMessage
                    && $log->getRequestPayload() === json_encode($request)
                    && $log->getResponsePayload() === json_encode($response);
            }))
        ;

        $this->logger->logOperation($merchant, $subMchId, $type, $success, $errorCode, $errorMessage, $request, $response);
    }

    public function testLogOperationWithFailedRequest(): void
    {
        $merchant = $this->createMock(Merchant::class);
        $subMchId = 'test_sub_mch_id';
        $type = ProfitShareOperationType::REQUEST_ORDER;
        $success = false;
        $errorCode = 'ERROR_CODE';
        $errorMessage = 'Error message';
        $request = 'string request';
        $response = null;

        $this->repository->expects($this->once())
            ->method('save')
            ->with(self::callback(function (ProfitShareOperationLog $log) use ($merchant, $subMchId, $type, $success, $errorCode, $errorMessage, $request, $response) {
                return $log->getMerchant() === $merchant
                    && $log->getSubMchId() === $subMchId
                    && $log->getType() === $type
                    && $log->isSuccess() === $success
                    && $log->getErrorCode() === $errorCode
                    && $log->getErrorMessage() === $errorMessage
                    && $log->getRequestPayload() === $request
                    && $log->getResponsePayload() === $response;
            }))
        ;

        $this->logger->logOperation($merchant, $subMchId, $type, $success, $errorCode, $errorMessage, $request, $response);
    }

    public function testLogOperationWithNullPayloads(): void
    {
        $merchant = $this->createMock(Merchant::class);
        $subMchId = 'test_sub_mch_id';
        $type = ProfitShareOperationType::QUERY_ORDER;
        $success = true;
        $errorCode = null;
        $errorMessage = null;
        $request = null;
        $response = null;

        $this->repository->expects($this->once())
            ->method('save')
            ->with(self::callback(function (ProfitShareOperationLog $log) use ($merchant, $subMchId, $type, $success, $errorCode, $errorMessage, $request, $response) {
                return $log->getMerchant() === $merchant
                    && $log->getSubMchId() === $subMchId
                    && $log->getType() === $type
                    && $log->isSuccess() === $success
                    && $log->getErrorCode() === $errorCode
                    && $log->getErrorMessage() === $errorMessage
                    && $log->getRequestPayload() === $request
                    && $log->getResponsePayload() === $response;
            }))
        ;

        $this->logger->logOperation($merchant, $subMchId, $type, $success, $errorCode, $errorMessage, $request, $response);
    }
}
