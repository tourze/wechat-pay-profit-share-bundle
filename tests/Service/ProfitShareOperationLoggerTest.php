<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOperationLog;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOperationType;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOperationLogRepository;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareOperationLogger;
use WechatPayBundle\Entity\Merchant;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(ProfitShareOperationLogger::class)]
final class ProfitShareOperationLoggerTest extends AbstractIntegrationTestCase
{
    private ProfitShareOperationLogger $logger;

    private ProfitShareOperationLogRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(ProfitShareOperationLogRepository::class);
        $this->logger = self::getService(ProfitShareOperationLogger::class);
    }

    public function testLogOperationWithSuccessfulRequest(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_id');
        $merchant->setCertSerial('test_cert_serial');
        $merchant->setPemKey('test_pem_key');
        $merchant->setPublicKey('test_public_key');
        $merchant->setPublicKeyId('test_public_key_id');

        $subMchId = 'test_sub_mch_id';
        $type = ProfitShareOperationType::REQUEST_ORDER;
        $success = true;
        $errorCode = null;
        $errorMessage = null;
        $request = ['key' => 'value'];
        $response = ['result' => 'success'];

        // Persist merchant first
        $em = self::getService(EntityManagerInterface::class);
        $em->persist($merchant);
        $em->flush();

        $this->logger->logOperation($merchant, $subMchId, $type, $success, $errorCode, $errorMessage, $request, $response);

        // Verify via repository
        $logs = $this->repository->findBy(['subMchId' => $subMchId]);
        $this->assertCount(1, $logs);

        $log = $logs[0];
        $this->assertSame($merchant->getId(), $log->getMerchant()?->getId());
        $this->assertSame($subMchId, $log->getSubMchId());
        $this->assertSame($type, $log->getType());
        $this->assertTrue($log->isSuccess());
        $this->assertNull($log->getErrorCode());
        $this->assertNull($log->getErrorMessage());
        $this->assertSame(json_encode($request), $log->getRequestPayload());
        $this->assertSame(json_encode($response), $log->getResponsePayload());
    }

    public function testLogOperationWithFailedRequest(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_id_2');
        $merchant->setCertSerial('test_cert_serial');
        $merchant->setPemKey('test_pem_key');
        $merchant->setPublicKey('test_public_key');
        $merchant->setPublicKeyId('test_public_key_id');

        $subMchId = 'test_sub_mch_id_failed';
        $type = ProfitShareOperationType::REQUEST_ORDER;
        $success = false;
        $errorCode = 'ERROR_CODE';
        $errorMessage = 'Error message';
        $request = 'string request';
        $response = null;

        // Persist merchant first
        $em = self::getService(EntityManagerInterface::class);
        $em->persist($merchant);
        $em->flush();

        $this->logger->logOperation($merchant, $subMchId, $type, $success, $errorCode, $errorMessage, $request, $response);

        // Verify via repository
        $logs = $this->repository->findBy(['subMchId' => $subMchId]);
        $this->assertCount(1, $logs);

        $log = $logs[0];
        $this->assertFalse($log->isSuccess());
        $this->assertSame($errorCode, $log->getErrorCode());
        $this->assertSame($errorMessage, $log->getErrorMessage());
        $this->assertSame($request, $log->getRequestPayload());
        $this->assertNull($log->getResponsePayload());
    }

    public function testLogOperationWithNullPayloads(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_id_3');
        $merchant->setCertSerial('test_cert_serial');
        $merchant->setPemKey('test_pem_key');
        $merchant->setPublicKey('test_public_key');
        $merchant->setPublicKeyId('test_public_key_id');

        $subMchId = 'test_sub_mch_id_null';
        $type = ProfitShareOperationType::QUERY_ORDER;
        $success = true;
        $errorCode = null;
        $errorMessage = null;
        $request = null;
        $response = null;

        // Persist merchant first
        $em = self::getService(EntityManagerInterface::class);
        $em->persist($merchant);
        $em->flush();

        $this->logger->logOperation($merchant, $subMchId, $type, $success, $errorCode, $errorMessage, $request, $response);

        // Verify via repository
        $logs = $this->repository->findBy(['subMchId' => $subMchId]);
        $this->assertCount(1, $logs);

        $log = $logs[0];
        $this->assertNull($log->getRequestPayload());
        $this->assertNull($log->getResponsePayload());
    }

    public function testServiceIsRegisteredInContainer(): void
    {
        $logger = self::getService(ProfitShareOperationLogger::class);
        $this->assertInstanceOf(ProfitShareOperationLogger::class, $logger);
    }
}
