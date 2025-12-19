<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

/**
 * 分账接收方表单类型
 *
 * 用于在创建分账订单时动态添加接收方信息
 */
final class ProfitShareReceiverType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => '接收方类型',
                'choices' => [
                    '商户号' => 'MERCHANT_ID',
                    '个人OpenID' => 'PERSONAL_OPENID',
                ],
                'constraints' => [
                    new NotBlank(['message' => '请选择接收方类型']),
                ],
            ])
            ->add('account', TextType::class, [
                'label' => '接收方账号',
                'attr' => [
                    'maxlength' => 64,
                    'placeholder' => '商户号或OpenID',
                ],
                'constraints' => [
                    new NotBlank(['message' => '请输入接收方账号']),
                ],
            ])
            ->add('name', TextType::class, [
                'label' => '接收方姓名',
                'required' => false,
                'attr' => [
                    'maxlength' => 1024,
                    'placeholder' => '可选，个人需要提供',
                ],
            ])
            ->add('amount', IntegerType::class, [
                'label' => '分账金额（分）',
                'attr' => [
                    'min' => 1,
                    'placeholder' => '金额必须大于0',
                ],
                'constraints' => [
                    new NotBlank(['message' => '请输入分账金额']),
                    new Positive(['message' => '分账金额必须大于0']),
                ],
            ])
            ->add('description', TextType::class, [
                'label' => '分账描述',
                'attr' => [
                    'maxlength' => 80,
                    'placeholder' => '分账说明',
                ],
                'constraints' => [
                    new NotBlank(['message' => '请输入分账描述']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
