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
 * @copyright  Copyright (c) 2014 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Widget_Form_Container
    extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('bl/customgrid/widget/form/container.phtml');
    }
    
    protected function _removeButtons(array $buttons)
    {
        foreach ($buttons as $button) {
            $this->_removeButton($button);
        }
        return $this;
    }
    
    public function getFormHtmlId()
    {
        return ($dataForm = $this->getChild('form')->getForm())
            ? $dataForm->getId()
            : $this->getChild('form')->getFormId();
    }
    
    public function getUseDefaultForm()
    {
        return $this->getDataSetDefault('use_default_form', true);
    }
}