<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\ProgressiveWebApp\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Zend_Db_Exception;

/**
 * Class InstallSchema
 * @package Tigren\ProgressiveWebApp\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * install tables
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     * @throws Zend_Db_Exception
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;
        $installer->startSetup();
        $table = $installer->getConnection()->newTable(
            $installer->getTable('tigren_pwa_notification')
        )
            ->addColumn(
                'notification_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'nullable' => false,
                    'primary' => true,
                    'unsigned' => true,
                ],
                'Notification ID'
            )
            ->addColumn(
                'title',
                Table::TYPE_TEXT,
                255,
                ['nullable => false'],
                'Title'
            )
            ->addColumn(
                'icon',
                Table::TYPE_TEXT,
                null,
                ['nullable => false'],
                'Icon'
            )
            ->addColumn(
                'body',
                Table::TYPE_TEXT,
                null,
                [],
                'Body'
            )
            ->addColumn(
                'target_url',
                Table::TYPE_TEXT,
                255,
                [],
                'Target Url'
            )
            ->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                [],
                'Created At'
            )
            ->addColumn(
                'modified',
                Table::TYPE_TIMESTAMP,
                null,
                [],
                'Modified'
            )
            ->setComment('Notification Table');
        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}