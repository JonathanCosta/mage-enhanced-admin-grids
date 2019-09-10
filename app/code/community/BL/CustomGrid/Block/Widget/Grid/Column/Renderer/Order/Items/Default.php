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
 
class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Order_Items_Default extends BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Sales_Items_Abstract
{
    protected function _getItemsBlockType()
    {
        return 'adminhtml/sales_order_view_items';
    }
    
    protected function _getActionLayoutHandle()
    {
        return 'adminhtml_sales_order_view';
    }
    
    protected function _getItemsBlockLayoutName()
    {
        return 'order_items';
    }
    
    protected function _getItemsBlockDefaultTemplate()
    {
        return 'sales/order/view/items.phtml';
    }
    
    protected function _prepareItemsBlock(Varien_Object $row)
    {
        $this->setOrder($row);
        return $this;
    }
}
