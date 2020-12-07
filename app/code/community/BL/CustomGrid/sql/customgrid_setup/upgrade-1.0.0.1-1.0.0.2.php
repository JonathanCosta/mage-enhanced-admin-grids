<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   BL
 * @package    BL_CustomGrid
 * @copyright  Copyright (c) 2016 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;

$installer->startSetup();

$connection = $installer->getConnection();

$optionsSourceTable = $installer->getTable('customgrid/options_source');
$optionsSourceModelTable = $installer->getTable('customgrid/options_source_model');

$connection->query("
    INSERT INTO `{$optionsSourceTable}` (`name`, `type`)
    VALUES ('Stores', 'mage_model');

    INSERT INTO `{$optionsSourceModelTable}` (`source_id`, `model_name`, `model_type`, `method`, `return_type`, `value_key`, `label_key`)
    VALUES (LAST_INSERT_ID(), 'adminhtml/system_config_source_store', 'singleton', 'toOptionArray', 'options_array', 'value', 'label');
");

$installer->endSetup();
