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

class BL_CustomGrid_Model_Column_Renderer_Source_Cms_Template_Processor
{
    public function toOptionArray()
    {
        $helper = Mage::helper('customgrid');
        
        return array(
            array(
                'value' => BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Text::CMS_TEMPLATE_PROCESSOR_NONE,
                'label' => $helper->__('No'),
            ),
            array(
                'value' => BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Text::CMS_TEMPLATE_PROCESSOR_BLOCK,
                'label' => $helper->__('CMS block template processor'),
            ),
            array(
                'value' => BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Text::CMS_TEMPLATE_PROCESSOR_PAGE,
                'label' => $helper->__('CMS page template processor'),
            ),
        );
    }
}