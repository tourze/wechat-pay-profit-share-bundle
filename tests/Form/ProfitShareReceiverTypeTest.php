<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Form;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tourze\WechatPayProfitShareBundle\Form\ProfitShareReceiverType;

/**
 * 分账接收方表单类型测试
 * @internal
 */
#[CoversClass(ProfitShareReceiverType::class)]
final class ProfitShareReceiverTypeTest extends TestCase
{
    private ProfitShareReceiverType $formType;

    protected function setUp(): void
    {
        $this->formType = new ProfitShareReceiverType();
    }

    public function testBuildFormAddsAllFields(): void
    {
        $fields = [];

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->method('add')
            ->willReturnCallback(function (string $name, string $type, array $options = []) use (&$fields, $builder) {
                $fields[$name] = ['type' => $type, 'options' => $options];

                return $builder;
            })
        ;

        $this->formType->buildForm($builder, []);

        $this->assertArrayHasKey('type', $fields);
        $this->assertArrayHasKey('account', $fields);
        $this->assertArrayHasKey('name', $fields);
        $this->assertArrayHasKey('amount', $fields);
        $this->assertArrayHasKey('description', $fields);
    }

    public function testBuildFormFieldTypes(): void
    {
        $fields = [];

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->method('add')
            ->willReturnCallback(function (string $name, string $type, array $options = []) use (&$fields, $builder) {
                $fields[$name] = ['type' => $type, 'options' => $options];

                return $builder;
            })
        ;

        $this->formType->buildForm($builder, []);

        $this->assertSame(ChoiceType::class, $fields['type']['type']);
        $this->assertSame(TextType::class, $fields['account']['type']);
        $this->assertSame(TextType::class, $fields['name']['type']);
        $this->assertSame(IntegerType::class, $fields['amount']['type']);
        $this->assertSame(TextType::class, $fields['description']['type']);
    }

    public function testBuildFormTypeFieldHasChoices(): void
    {
        $fields = [];

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->method('add')
            ->willReturnCallback(function (string $name, string $type, array $options = []) use (&$fields, $builder) {
                $fields[$name] = ['type' => $type, 'options' => $options];

                return $builder;
            })
        ;

        $this->formType->buildForm($builder, []);

        $this->assertArrayHasKey('choices', $fields['type']['options']);
        $this->assertContains('MERCHANT_ID', $fields['type']['options']['choices']);
        $this->assertContains('PERSONAL_OPENID', $fields['type']['options']['choices']);
    }

    public function testBuildFormNameFieldIsOptional(): void
    {
        $fields = [];

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->method('add')
            ->willReturnCallback(function (string $name, string $type, array $options = []) use (&$fields, $builder) {
                $fields[$name] = ['type' => $type, 'options' => $options];

                return $builder;
            })
        ;

        $this->formType->buildForm($builder, []);

        $this->assertArrayHasKey('required', $fields['name']['options']);
        $this->assertFalse($fields['name']['options']['required']);
    }

    public function testConfigureOptionsHasDefaults(): void
    {
        $resolver = new OptionsResolver();

        $this->formType->configureOptions($resolver);

        $resolvedOptions = $resolver->resolve([]);
        $this->assertNull($resolvedOptions['data_class']);
    }

    public function testBuildFormRequiredFieldsHaveConstraints(): void
    {
        $fields = [];

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->method('add')
            ->willReturnCallback(function (string $name, string $type, array $options = []) use (&$fields, $builder) {
                $fields[$name] = ['type' => $type, 'options' => $options];

                return $builder;
            })
        ;

        $this->formType->buildForm($builder, []);

        // type, account, amount, description 应该有约束
        $this->assertArrayHasKey('constraints', $fields['type']['options']);
        $this->assertArrayHasKey('constraints', $fields['account']['options']);
        $this->assertArrayHasKey('constraints', $fields['amount']['options']);
        $this->assertArrayHasKey('constraints', $fields['description']['options']);

        // name 是可选的，不应该有 NotBlank 约束
        $this->assertArrayNotHasKey('constraints', $fields['name']['options']);
    }

    public function testBuildFormFieldLabels(): void
    {
        $fields = [];

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->method('add')
            ->willReturnCallback(function (string $name, string $type, array $options = []) use (&$fields, $builder) {
                $fields[$name] = ['type' => $type, 'options' => $options];

                return $builder;
            })
        ;

        $this->formType->buildForm($builder, []);

        $this->assertSame('接收方类型', $fields['type']['options']['label']);
        $this->assertSame('接收方账号', $fields['account']['options']['label']);
        $this->assertSame('接收方姓名', $fields['name']['options']['label']);
        $this->assertSame('分账金额（分）', $fields['amount']['options']['label']);
        $this->assertSame('分账描述', $fields['description']['options']['label']);
    }
}
