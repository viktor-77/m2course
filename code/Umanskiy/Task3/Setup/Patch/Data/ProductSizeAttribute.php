<?php declare(strict_types=1);

namespace Umanskiy\Task3\Setup\Patch\Data;

use Magento\Cms\Model\ResourceModel\Block\CollectionFactory as CmsBlockCollectionFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Psr\Log\LoggerInterface;

class ProductSizeAttribute implements DataPatchInterface, PatchRevertableInterface
{
    private EavSetupFactory $eavSetupFactory;

    private ModuleDataSetupInterface $moduleDataSetup;

    private CmsBlockCollectionFactory $cmsBlockCollectionFactory;

    private LoggerInterface $logger;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param CmsBlockCollectionFactory $cmsBlockCollectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleDataSetupInterface  $moduleDataSetup,
        EavSetupFactory           $eavSetupFactory,
        CmsBlockCollectionFactory $cmsBlockCollectionFactory,
        LoggerInterface           $logger
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->cmsBlockCollectionFactory = $cmsBlockCollectionFactory;
        $this->logger = $logger;
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

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function apply(): void
    {
        try {
            $options = $this->getEnabledCmsBlockIdentifiers();
            $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup])->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'product_size_guide',
                [
                    'group' => 'Default',
                    'type' => 'int',
                    'label' => 'Size Guide',
                    'input' => 'select',
                    'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Table',
                    'required' => true,
                    'sort_order' => 100,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'option' => [
                        'values' => $options
                    ],
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function revert(): void
    {
        try {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'product_size_guide');
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }

    /**
     * @return array
     */
    private function getEnabledCmsBlockIdentifiers(): array
    {
        $collection = $this->cmsBlockCollectionFactory->create();
        $collection->addFieldToFilter('is_active', 1);
        $collection->addFieldToSelect('identifier');

        return $collection->getColumnValues('identifier');
    }

}
