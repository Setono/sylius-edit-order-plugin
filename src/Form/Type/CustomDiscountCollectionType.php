<?php

declare(strict_types=1);

namespace Setono\SyliusOrderEditPlugin\Form\Type;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Order\Factory\AdjustmentFactoryInterface;
use Sylius\Component\Order\Model\AdjustableInterface;
use Sylius\Component\Order\Model\AdjustmentInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\Assert\Assert;

abstract class CustomDiscountCollectionType extends AbstractType
{
    public function __construct(
        protected readonly AdjustmentFactoryInterface $adjustmentFactory,
        protected readonly string $label,
        protected readonly string $adjustmentType,
    ) {
    }

    public function getParent(): string
    {
        return CollectionType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'entry_type' => OrderDiscountType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'label' => 'sylius.form.order.discounts',
            'entry_options' => [
                'label' => false,
            ],
            'getter' =>
                /** @param OrderItemInterface|OrderInterface $adjustable */
                function (AdjustableInterface &$adjustable): array {
                    Assert::isInstanceOfAny($adjustable, [OrderInterface::class, OrderItemInterface::class]);
                    /** @var Collection $adjustments */
                    $adjustments = $adjustable->getAdjustmentsRecursively($this->adjustmentType);

                    $notDistributedAdjustments = [];
                    /** @var AdjustmentInterface $adjustment */
                    foreach ($adjustments as $adjustment) {
                        /** @var string $originCode */
                        $originCode = $adjustment->getOriginCode();

                        if (isset($notDistributedAdjustments[$originCode])) {
                            $notDistributedAdjustments[$originCode] += ($adjustment->getAmount()) * -1;

                            continue;
                        }

                        $notDistributedAdjustments[$originCode] = ($adjustment->getAmount()) * -1;
                    }

                    return $notDistributedAdjustments;
                },
            'setter' => function (AdjustableInterface &$adjustable, array $discounts): void {
                $this->setDiscounts($adjustable, $discounts);
            },
        ]);
    }

    abstract public function setDiscounts(AdjustableInterface $adjustable, array $discounts): void;
}
