<?php

namespace Umanskiy\Task4\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Validator\ValidateException;
use Psr\Log\LoggerInterface;

class AddFeaturedProductAttribute implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory,
        private LoggerInterface $logger
    ) {}

    /**
     * @return void
     * @throws LocalizedException
     * @throws ValidateException
     */
    public function apply()
    {
        try {
            $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup])->addAttribute(
                Product::ENTITY,
                'featured',
                [
                    'visible_on_front' => true,
                    'visible' => true,
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'label' => 'Featured',
                    'type' => 'int',
                    'input' => 'boolean',
                    'required' => false,
                    'is_filterable_in_grid' => true,
                    'sort_order' => 10,
                    'group' => 'Default',
                ]
            );
        } catch (LocalizedException | ValidateException $e) {
            $this->logger->error('Error while adding the featured product attribute: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function revert()
    {
        try {
            /** @var EavSetup $eavSetup */
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

            $eavSetup->removeAttribute(Product::ENTITY, 'featured');
        } catch (LocalizedException $e) {
            $this->logger->error('Error while removing the featured product attribute: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
