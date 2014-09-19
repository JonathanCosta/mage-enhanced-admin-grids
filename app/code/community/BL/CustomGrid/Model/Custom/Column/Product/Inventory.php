<?php

class BL_CustomGrid_Model_Custom_Column_Product_Inventory
    extends BL_CustomGrid_Model_Custom_Column_Simple_Table
{
    protected function _prepareConfig()
    {
        $helper = $this->_getBaseHelper();
        
        $notes = array(
            'use_config_filter' => 'Choose "Yes" to filter on products that use system configuration values or not.'
                . ' Else, the filter type will depend on the type of the field',
            'use_config_prefix' => 'Prefix that will be prepended to the values coming from the system configuration',
            'use_config_suffix' => 'Suffix that will be appended to the values coming from the system configuration',
            'warning' => 'Sorting does not take system configuration values into account, so that products may not be'
                . ' sorted consistently',
        );
        
        if ($this->getIsUseConfigField()) {
            $this->addCustomizationParam('use_config_filter', array(
                'label'        => $helper->__('Filter on "Use Config"'),
                'description'  => $helper->__($notes['use_config_filter']),
                'type'         => 'select',
                'source_model' => 'adminhtml/system_config_source_yesno',
                'value'        => 0,
            ), 10);
            
            $this->addCustomizationParam('use_config_prefix', array(
                'label'       => $helper->__('Config Values Prefix'),
                'description' => $helper->__($notes['use_config_prefix']),
                'type'        => 'text',
                'value'       => '',
            ), 20);
            
            $this->addCustomizationParam('use_config_suffix', array(
                'label'       => $helper->__('Config Values Suffix'),
                'description' => $helper->__($notes['use_config_suffix']),
                'type'        => 'text',
                'value'       => '',
            ), 30);
            
            $this->setWarning($helper->__($notes['warning']));
            $this->setCustomizationWindowConfig(array('height' => 280), true);
        }
        
        return $this;
    }
    
    public function getTableName()
    {
        return 'cataloginventory/stock_item';
    }
    
    public function getJoinConditionMainFieldName()
    {
        return 'entity_id';
    }
    
    public function getJoinConditionTableFieldName()
    {
        return 'product_id';
    }
    
    public function getIsUseConfigField()
    {
        return (bool) $this->getConfigParam('is_use_config_field');
    }
    
    public function getUseConfigFieldName()
    {
        return (!$fieldName = $this->getConfigParam('use_config_field_name'))
            ? 'use_config_' . $this->getTableFieldName(true)
            : $fieldName;
    }
    
    public function getUseConfigSystemPath()
    {
        return (!$path = $this->getConfigParam('system_config_path'))
            ? 'cataloginventory/item_options/' . $this->getTableFieldName()
            : $path;
    }
    
    public function getFieldType()
    {
        return $this->getConfigParam('field_type');
    }
    
    protected function _getAdditionalJoinConditions($columnIndex, array $params,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock, Varien_Data_Collection_Db $collection, $mainAlias, $tableAlias)
    {
        list($adapter, $qi) = $this->_getCollectionAdapter($collection, true);
        // @todo "stock_id" is usually hard-coded, should we provide it as a customization parameter anyway ?
        return array($adapter->quoteInto($qi($tableAlias . '.stock_id') . ' = ?', 1));
    }
    
    protected function _addFieldToSelect(Varien_Db_Select $select, $columnIndex, $tableAlias, array $params,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock, Varien_Data_Collection_Db $collection)
    {
        $helper = $this->_getCollectionHelper();
        list($adapter, $qi) = $this->_getCollectionAdapter($collection, true);
        
        $fieldName = $this->getTableFieldName();
        $select->columns(array($columnIndex => $tableAlias . '.' . $fieldName), $tableAlias);
        $helper->addFilterToCollectionMap($collection, $qi($tableAlias . '.' . $fieldName), $columnIndex);
        
        if ($this->getIsUseConfigField()) {
            $fieldName = $this->getUseConfigFieldName();
            $columnIndex .= '_cpi_uc';
            $select->columns(array($columnIndex => $tableAlias . '.' . $fieldName), $tableAlias);
            $helper->addFilterToCollectionMap($collection, $qi($tableAlias . '.' . $fieldName), $columnIndex);
        }
        
        return $this;
    }
    
    public function addFilterToGridCollection($collection, Mage_Adminhtml_Block_Widget_Grid_Column $columnBlock)
    {
        $fieldName  = ($columnBlock->getFilterIndex() ? $columnBlock->getFilterIndex() : $columnBlock->getIndex());
        $tableAlias = $columnBlock->getBlcgTableAlias();
        $condition  = $columnBlock->getFilter()->getCondition();
        $params = $columnBlock->getBlcgFilterParams();
        
        if ($fieldName && is_array($condition) && $tableAlias) {
            list($adapter, $qi) = $this->_getCollectionAdapter($collection, true);
            
            if (is_array($params) && $this->_extractBoolParam($params, 'use_config_filter', false)) {
                if (isset($condition['eq'])) {
                    $collection->getSelect()
                        ->where(
                            $qi($tableAlias . '.' . $this->getUseConfigFieldName())
                            . ' = '
                            . $adapter->quoteInto('?', (bool) $condition['eq'])
                        );
                }
            } else {
                if ($column->getCanUseConfig()) {
                    $conditionBase = new Zend_Db_Expr(
                        'IF('
                        . $qi($tableAlias . '.' . $this->getUseConfigFieldName()) . ','
                        . $adapter->quoteInto('?', Mage::getStoreConfig($this->getUseConfigSystemPath())) . ','
                        . $qi($tableAlias . '.' . $this->getTableFieldName())
                        . ')'
                    );
                } else {
                    $conditionBase = $qi($tableAlias . '.' . $this->getTableFieldName());
                }
                
                if ($this->getFieldType() == 'decimal') {
                    if (isset($condition['from']) && isset($condition['to'])) {
                        $collection->getSelect()
                            ->where(
                                $conditionBase
                                . ' BETWEEN '
                                . $adapter->quoteInto('?', floatval($condition['from']))
                                . ' AND '
                                . $adapter->quoteInto('?', floatval($condition['to']))
                            );
                    }
                } elseif (isset($condition['eq'])) {
                    $collection->getSelect()
                        ->where($conditionBase . ' = ' . $adapter->quoteInto('?', $condition['eq']));
                }
            }
        }
        
        return $this;
    }
    
    public function shouldInvalidateFilters(BL_CustomGrid_Model_Grid $gridModel,
        BL_CustomGrid_Model_Grid_Column $columnModel, array $params, array $renderers)
    {
        if (!parent::shouldInvalidateFilters($gridModel, $columnModel, $params, $renderers)) {
            if ($this->getIsUseConfigField()) {
                return ($this->_extractBoolParam($params['previous'], 'use_config_filter')
                    XOR $this->_extractBoolParam($params['current'],  'use_config_filter'));
            }
            return false;
        }
        return true;
    }
    
    protected function _getConditionalBlockValues(array $params)
    {
        $helper = $this->_getBaseHelper();
        $values = array();
        $fieldType = $this->getFieldType();
        
        if (($fieldType == 'options')
            && ($sourceModel = Mage::getModel($this->getConfigParam('source_model')))
            && method_exists($sourceModel, 'toOptionArray')) {
            $optionsArray = $sourceModel->toOptionArray();
            $optionsHash  = $helper->getOptionsHashFromOptionsArray($optionsArray);
            
            $values = array(
                'filter'  => 'customgrid/widget_grid_column_filter_select',
                'options' => $optionsArray,
                'options_hash' => $optionsHash,
            );
        }
        
        if ($this->_extractBoolParam($params, 'use_config_filter', false)) {
            $values['filter']  = 'customgrid/widget_grid_column_filter_select';
            $values['options'] = array(
                array('value' => 1, 'label' => $helper->__('Use Config')),
                array('value' => 0, 'label' => $helper->__('Do Not Use Config')),
            );
        } elseif ($fieldType == 'boolean') {
            $values['filter'] = 'customgrid/widget_grid_column_filter_yesno';
        } elseif ($fieldType == 'decimal') {
            $values['filter'] = 'adminhtml/widget_grid_column_filter_range';
        } elseif ($fieldType != 'options') {
            $values['filter'] = 'adminhtml/widget_grid_column_filter_text';
        }
        
        return $values;
    }
    
    protected function _getForcedBlockValues(Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel, $columnBlockId, $columnIndex, array $params, Mage_Core_Model_Store $store)
    {
        return array_merge(
            array(
                'renderer'   => 'customgrid/widget_grid_column_renderer_product_inventory',
                'field_name' => $this->getTableFieldName(),
                'field_type' => $this->getFieldType(),
                'can_use_config'     => $this->getIsUseConfigField(),
                'use_config_index'   => $columnIndex . '_cpi_uc',
                'use_config_prefix'  => $this->_extractStringParam($params, 'use_config_prefix', ''),
                'use_config_suffix'  => $this->_extractStringParam($params, 'use_config_suffix', ''),
                'system_config_path' => $this->getUseConfigSystemPath(),
                'blcg_filter_params' => $params,
                'filter_condition_callback' => array($this, 'addFilterToGridCollection'),
            ),
            $this->_getConditionalBlockValues($params)
        );
    }
}