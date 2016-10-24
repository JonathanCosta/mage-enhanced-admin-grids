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
 * @copyright  Copyright (c) 2015 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Blcg_GridController extends BL_CustomGrid_Controller_Grid_Action
{
    /**
     * Return grid column model
     * 
     * @return BL_CustomGrid_Model_Grid_Column
     */
    protected function _getGridColumnModel()
    {
        return Mage::getSingleton('customgrid/grid_column');
    }
    
    /**
     * Load layout and initialize active menu, title and breadcrumbs for a common system page action
     * 
     * @return BL_CustomGrid_Blcg_GridController
     */
    protected function _initSystemPageAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('system/customgrid')
            ->_title($this->__('Custom Grids'))
            ->_addBreadcrumb($this->__('Custom Grids'), $this->__('Custom Grids'));
        return $this;
    }
    
    public function indexAction()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $this->_forward('grid');
            return;
        }
        $this->_initSystemPageAction()->renderLayout();
    }
    
    public function gridAction()
    {
        $this->loadLayout()->renderLayout();
    }
    
    public function reapplyDefaultFilterAction()
    {
        $isSuccess = false;
        $resultMessage = '';
        
        try {
            $gridModel = $this->_initGridModel();
            $this->_initGridProfile();
            $blockFilterVarName = $gridModel->getBlockVarName(BL_CustomGrid_Model_Grid::GRID_PARAM_FILTER);
            
            if ($sessionKey = $gridModel->getBlockParamSessionKey($blockFilterVarName)) {
                $this->_getSession()->unsetData($sessionKey);
            }
            
            $isSuccess = true;
            
        } catch (Mage_Core_Exception $e) {
            $resultMessage = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $resultMessage = $this->__('An error occurred while reapplying the default filter');
        }
        
        if ($isSuccess) {
            $this->_setActionSuccessJsonResponse();
        } else {
            $this->_setActionErrorJsonResponse($resultMessage);
        }
    }
    
    public function columnsListFormAction()
    {
        $this->_prepareWindowGridFormLayout(
            'columns_list',
            array(),
            BL_CustomGrid_Model_Grid_Sentry::ACTION_CUSTOMIZE_COLUMNS,
            true,
            array('default', 'adminhtml_blcg_grid_columns_list_form')
        );
        
        if (($containerBlock = $this->getLayout()->getBlock('blcg.grid.form_container'))
            && ($formBlock = $containerBlock->getChild('form'))) {
            /**
             * @var $containerBlock BL_CustomGrid_Block_Grid_Form_Container
             * @var $formBlock BL_CustomGrid_Block_Grid_Form_Columns_List
             */
            $formBlock->prepareFormContainer($containerBlock);
        }
        
        $this->renderLayout();
    }
    
    public function saveColumnsAction()
    {
        $isSuccess = false;
        $resultMessage = '';
        
        try {
            if (!$this->getRequest()->isPost()
                || !is_array($columns = $this->getRequest()->getParam('columns'))) {
                Mage::throwException($this->__('Invalid request'));
            }
            
            $gridModel = $this->_initGridModel();
            $this->_initGridProfile();
            $this->_getGridColumnModel()->updateGridModelColumns($gridModel, $columns)->save();
            
            $isSuccess = true;
            
        } catch (Mage_Core_Exception $e) {
            $resultMessage = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $resultMessage = $this->__('An error occurred while saving the columns');
        }
        
        if ($isSuccess) {
            $this->_getBlcgSession()->addSuccess($this->__('The columns have been successfully updated'));
            $this->_setActionSuccessJsonResponse(array(), false);
        } else {
            $this->_setActionErrorJsonResponse($resultMessage, false);
        }
    }
    
    public function customColumnsFormAction()
    {
        $this->_prepareWindowGridFormLayout(
            'custom_columns',
            array(),
            BL_CustomGrid_Model_Grid_Sentry::ACTION_CUSTOMIZE_COLUMNS
        );
        $this->renderLayout();
    }
    
    public function saveCustomColumnsAction()
    {
        $isSuccess = false;
        $resultMessage = '';
        
        try {
            if (!$this->getRequest()->isPost()) {
                Mage::throwException($this->__('Invalid request'));
            }
            
            $this->_saveConfigFormFieldsetsStates();
            
            $gridModel = $this->_initGridModel();
            $this->_initGridProfile();
            $customColumns = $this->getRequest()->getParam('custom_columns', array());
            $this->_getGridColumnModel()->updateGridModelCustomColumns($gridModel, $customColumns)->save();
            
            $isSuccess = true;
            
        } catch (Mage_Core_Exception $e) {
            $resultMessage = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $resultMessage = $this->__('An error occurred while saving the custom columns');
        }
        
        if ($isSuccess) {
            $this->_getBlcgSession()->addSuccess($this->__('The custom columns have been successfully updated'));
            $this->_setActionSuccessJsonResponse();
        } else {
            $this->_setActionErrorJsonResponse($resultMessage);
        }
    }
    
    public function defaultParamsFormAction()
    {
        if ($defaultParams = $this->getRequest()->getParam('default_params', '')) {
            /** @var $helper BL_CustomGrid_Helper_Data */
            $helper = Mage::helper('customgrid');
            $defaultParams = $helper->unserializeArray($defaultParams);
        } else {
            $defaultParams = array();
        }
        
        $this->_prepareWindowGridFormLayout(
            'default_params',
            array('default_params' => $defaultParams),
            BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_DEFAULT_PARAMS
        );
        
        $this->renderLayout();
    }
    
    public function saveDefaultParamsAction()
    {
        $isSuccess = false;
        $resultMessage = '';
        
        try {
            if (!$this->getRequest()->isPost()) {
                Mage::throwException($this->__('Invalid request'));
            }
            
            $this->_saveConfigFormFieldsetsStates();
            $this->_initGridModel();
            $gridProfile = $this->_initGridProfile();
            
            $appliableParams = $this->getRequest()->getParam('appliable_default_params', array());
            $appliableValues = $this->getRequest()->getParam('appliable_values', array());
            $removableParams = $this->getRequest()->getParam('removable_default_params', array());
            
            if (is_array($appliableParams) && is_array($appliableValues)) {
                foreach ($appliableParams as $key => $isAppliable) {
                    if ($isAppliable && isset($appliableValues[$key])) {
                        $appliableParams[$key] = $appliableValues[$key];
                    } else {
                        unset($appliableParams[$key]);
                    }
                }
            } else {
                $appliableParams = array();
            }
            
            $gridProfile->updateDefaultParams($appliableParams, $removableParams);
            $isSuccess = true;
            
        } catch (Mage_Core_Exception $e) {
            $resultMessage = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $resultMessage = $this->__('An error occurred while saving the default parameters');
        }
        
        if ($isSuccess) {
            $this->_getBlcgSession()->addSuccess($this->__('The default parameters have been successfully updated'));
            $this->_setActionSuccessJsonResponse();
        } else {
            $this->_setActionErrorJsonResponse($resultMessage);
        }
    }
    
    public function gridInfosAction()
    {
        $this->_prepareWindowGridFormLayout(
            'grid_infos',
            array(),
            array(
                BL_CustomGrid_Model_Grid_Sentry::ACTION_VIEW_GRID_INFOS,
                BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_FORCED_TYPE,
            )
        );
        $this->renderLayout();
    }
    
    public function saveGridInfosAction()
    {
        $isSuccess = false;
        $resultMessage = '';
        
        try {
            if (!$this->getRequest()->isPost()) {
                Mage::throwException($this->__('Invalid request'));
            }
            
            $this->_saveConfigFormFieldsetsStates();
            
            $gridModel = $this->_initGridModel();
            $this->_initGridProfile();
            
            if ($this->getRequest()->has('disabled')) {
                $gridModel->setDisabled((bool) $this->getRequest()->getParam('disabled'));
            }
            if ($this->getRequest()->has('forced_type_code')) {
                $gridModel->updateForcedType($this->getRequest()->getParam('forced_type_code'));
            }
            
            $gridModel->save();
            $isSuccess = true;
            
        } catch (Mage_Core_Exception $e) {
            $resultMessage = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $resultMessage = $this->__('An error occurred while saving the grid infos');
        }
        
        if ($isSuccess) {
            $this->_getBlcgSession()->addSuccess($this->__('The grid infos have been successfully updated'));
            $this->_setActionSuccessJsonResponse();
        } else {
            $this->_setActionErrorJsonResponse($resultMessage);
        }
    }
    
    public function editAction()
    {
        try {
            $gridModel = $this->_initGridModel();
            
            if (!$this->getRequest()->has('profile_id')) {
                $this->getRequest()->setParam('profile_id', $gridModel->getProfileId());
            }
            
            $this->_initGridProfile();
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $this->_redirectReferer();
            return;
        }
        
        $gridTitle = $this->__('Custom Grid: %s', $gridModel->getBlockType()) . ' - ';
        
        if ($gridModel->getRewritingClassName()) {
            $gridTitle .= $gridModel->getRewritingClassName();
        } else {
            $gridTitle .= $this->__('Base Class');
        }
        
        $this->_initSystemPageAction()
            ->_title($gridTitle)
            ->_addBreadcrumb($gridTitle, $gridTitle)
            ->renderLayout();
    }
    
    /**
     * Apply the given grid informations values to the given grid model
     * 
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param array $values Grid informations values
     * @return BL_CustomGrid_GridController
     */
    protected function _applyGridInfosValues(BL_CustomGrid_Model_Grid $gridModel, array $values)
    {
        if (isset($values['disabled'])) {
            $gridModel->setDisabled((bool) $values['disabled']);
        }
        if (isset($values['forced_type_code'])) {
            $gridModel->updateForcedType($values['forced_type_code']);
        }
        return $this;
    }
    
    /**
     * Save the values from the grid edit page to the given grid model and profile
     * 
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param BL_CustomGrid_Model_Grid_Profile $gridProfile Grid profile
     * @param array $data Request data
     * @return BL_CustomGrid_GridController
     */
    protected function _saveGridEditValues(
        BL_CustomGrid_Model_Grid $gridModel,
        BL_CustomGrid_Model_Grid_Profile $gridProfile,
        array $data
    ) {
        $updateCallbacks = array(
            'columns' => array(
                'type' => 'grid',
                'callback' => array($this->_getGridColumnModel(), 'updateGridModelColumns'),
                'params_before' => array($gridModel),
            ),
            'grid' => array(
                'type' => 'grid',
                'callback' => array($this, '_applyGridInfosValues'),
                'params_before' => array($gridModel),
            ),
            'profiles_defaults' => array(
                'type' => 'grid',
                'callback' => array($gridModel, 'updateProfilesDefaults'),
            ),
            'customization_params' => array(
                'type' => 'grid',
                'callback' => array($gridModel, 'updateCustomizationParameters'),
            ),
            'default_params_behaviours' => array(
                'type' => 'grid',
                'callback' => array($gridModel->getDefaultParamsHandler(), 'updateDefaultParamsBehaviours'),
            ),
            'roles_permissions' => array(
                'type' => 'sentry',
                'callback' => array($gridModel->getSentry(), 'setGridRolesPermissions'),
            ),
            'profile_edit' => array(
                'type' => 'profile',
                'callback' => array($gridProfile, 'update'),
            ),
            'profile_assign' => array(
                'type' => 'profile',
                'callback' => array($gridProfile, 'assign'),
            ),
        );
        
        /** @var $transaction BL_CustomGrid_Model_Resource_Transaction */
        $transaction = Mage::getModel('customgrid/resource_transaction');
        $transaction->addObject($gridModel);
        
        foreach ($updateCallbacks as $key => $updateCallback) {
            if (isset($data[$key]) && is_array($data[$key])) {
                if (isset($updateCallback['params_before'])) {
                    $params = $updateCallback['params_before'];
                } else {
                    $params = array();
                }
                
                $params[] = $data[$key];
                
                if (isset($updateCallback['params_after'])) {
                    $params = array_merge($params, $updateCallback['params_after']);
                }
                
                if ($updateCallback['type'] == 'profile') {
                    $transaction->addParameterizedCommitCallback($updateCallback['callback'], array($data[$key]));
                } else {
                    call_user_func_array($updateCallback['callback'], $params);
                }
            }
        }
        
        $gridProfile->setIsBulkSaveMode(true);
        $transaction->save();
        $gridProfile->setIsBulkSaveMode(false);
        
        return $this;
    }
    
    public function saveAction()
    {
        $isSuccess = false;
        $resultMessage = '';
        
        try {
            if (!$this->getRequest()->isPost()) {
                Mage::throwException($this->__('Invalid request'));
            }
            
            $gridModel = $this->_initGridModel();
            $gridProfile = $this->_initGridProfile();
            
            $data = $this->getRequest()->getPost();
            $this->_applyUseConfigValuesToRequestData($data);
            $this->_saveGridEditValues($gridModel, $gridProfile, $data);
            
            $isSuccess = true;
            
        } catch (Mage_Core_Exception $e) {
            $resultMessage = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $resultMessage = $this->__('An error occurred while saving the grid');
        }
        
        if ($isSuccess) {
            $this->_getSession()->addSuccess($this->__('The custom grid has been successfully updated'));
        } else {
            $this->_getSession()->addError($resultMessage);
        }
        
        if ($isSuccess && isset($gridModel) && $this->getRequest()->getParam('back', false)) {
            $this->_redirect(
                '*/*/edit',
                array(
                    '_current'   => true,
                    'grid_id'    => $gridModel->getId(),
                    'profile_id' => $gridModel->getProfileId(),
                )
            );
        } else {
            $this->_redirect('*/*/index');
        }
    }
    
    /**
     * Apply a base action from the grids list page, by calling the given method on the current grid model,
     * and saving the grid model afterwards if required
     * 
     * @param string $methodName Method name to call on the grid model
     * @param array $parameters Method parameters
     * @param bool $saveAfter Whether the grid model should be saved after the method call
     * @param string $successMessage Success message
     * @param string $defaultErrorMessage Default error message to display if a non-Magento exception is caught
     */
    protected function _applyGridsListAction(
        $methodName,
        array $parameters,
        $saveAfter = false,
        $successMessage,
        $defaultErrorMessage
    ) {
         try {
            $gridModel = $this->_initGridModel();
            call_user_func_array(array($gridModel, $methodName), $parameters);
            
            if ($saveAfter) {
                $gridModel->save();
            }
            
            $this->_getSession()->addSuccess($this->__($successMessage));
            $this->_redirect('*/*/');
            return;
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($this->__($defaultErrorMessage));
        }
        $this->_redirectReferer();
    }
    
    public function disableAction()
    {
        $this->_applyGridsListAction(
            'setDisabled',
            array(true),
            true,
            'The custom grid has been successfully disabled',
            'An error occurred while disabling the grid'
        );
    }
    
    public function enableAction()
    {
        $this->_applyGridsListAction(
            'setDisabled',
            array(false),
            true,
            'The custom grid has been successfully enabled',
            'An error occurred while enabling the grid'
        );
    }
    
    public function deleteAction()
    {
        $this->_applyGridsListAction(
            'delete',
            array(),
            false,
            'The custom grid has been successfully deleted',
            'An error occurred while deleting the grid'
        );
    }
    
    protected function _isAllowed()
    {
        // Specific permissions are enforced by the models
        switch ($this->getRequest()->getActionName()) {
            case 'index':
            case 'grid':
                return $this->_getAdminSession()->isAllowed('customgrid/administration/view_grids_list');
        }
        return true;
    }
}
