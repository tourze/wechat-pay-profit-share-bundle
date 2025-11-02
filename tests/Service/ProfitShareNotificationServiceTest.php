<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOperationLogRepository;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareNotificationService;
use WeChatPay\Crypto\AesGcm;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Service\WechatPayBuilder;
use Yiisoft\Json\Json;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(ProfitShareNotificationService::class)]
class ProfitShareNotificationServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
    }

    public function testHandleNotification(): void
    {
        // 创建Mock依赖
        $operationRepository = $this->createMock(ProfitShareOperationLogRepository::class);
        $operationRepository->expects($this->once())->method('save');

        $builderFactory = $this->createMock(WechatPayBuilder::class);

        $keys = $this->generateKeyPair();

        $builderFactory->expects($this->once())
            ->method('getPlatformPublicKey')
            ->willReturn($keys['public'])
        ;

        // 将Mock依赖注入到容器中
        self::getContainer()->set(ProfitShareOperationLogRepository::class, $operationRepository);
        self::getContainer()->set(WechatPayBuilder::class, $builderFactory);

        // 从容器获取服务
        $service = self::getService(ProfitShareNotificationService::class);

        $merchant = new Merchant();
        $merchant->setMchId('1900000001');
        $merchant->setApiKey('01234567890123456789012345678901');

        $resource = [
            'sp_mchid' => '1900000100',
            'sub_mchid' => '1900000109',
            'transaction_id' => '4200000000000000000000000000',
            'order_id' => '1217752501201407033233368018',
            'out_order_no' => 'P20150806125346',
            'receiver' => [
                'type' => 'MERCHANT_ID',
                'account' => '1900000100',
                'amount' => 888,
                'description' => '分账',
            ],
            'success_time' => '2018-06-08T10:34:56+08:00',
        ];

        $associatedData = '';
        $nonce = '0123456789ab';
        $ciphertext = AesGcm::encrypt(Json::encode($resource), $merchant->getApiKey() ?? '', $nonce, $associatedData);

        $payload = [
            'id' => 'EV-2018022511223320873',
            'create_time' => '2018-06-08T10:34:56+08:00',
            'event_type' => 'TRANSACTION.SUCCESS',
            'resource_type' => 'encrypt-resource',
            'resource' => [
                'algorithm' => 'AEAD_AES_256_GCM',
                'original_type' => 'profitsharing',
                'ciphertext' => $ciphertext,
                'associated_data' => $associatedData,
                'nonce' => $nonce,
            ],
        ];

        $body = Json::encode($payload);

        $timestamp = (string) time();
        $signatureNonce = 'signature-nonce';
        $message = $timestamp . "\n" . $signatureNonce . "\n" . $body . "\n";
        $privateKey = $keys['private'];
        if (false === $privateKey) {
            throw new \RuntimeException('Private key is empty');
        }
        /** @var \OpenSSLAsymmetricKey|\OpenSSLCertificate|string $privateKey */
        openssl_sign($message, $signatureBinary, $privateKey, OPENSSL_ALGO_SHA256);
        /** @var string $signatureBinary */
        $signature = base64_encode($signatureBinary);

        $headers = [
            'Wechatpay-Timestamp' => $timestamp,
            'Wechatpay-Nonce' => $signatureNonce,
            'Wechatpay-Signature' => $signature,
        ];

        $result = $service->handleNotification($merchant, $body, $headers);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('resource', $result);
        $this->assertIsArray($result['resource']);
        $this->assertArrayHasKey('sub_mchid', $result['resource']);
        $this->assertSame('1900000109', $result['resource']['sub_mchid']);
    }

    /**
     * @return array{public: mixed, private: mixed}
     */
    private function generateKeyPair(): array
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        if (false === $resource) {
            throw new \RuntimeException('Failed to generate key pair');
        }

        $details = openssl_pkey_get_details($resource);
        if (false === $details) {
            throw new \RuntimeException('Failed to get key details');
        }
        /** @var array<string, mixed> $details */
        $publicKey = $details['key'];

        return [
            'public' => $publicKey,
            'private' => $resource,
        ];
    }
}
