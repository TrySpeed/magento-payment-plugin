<?php

namespace Tryspeed\BitcoinPayment\Block\Adminhtml\System\Config\Renderer;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class CustomRadioRenderer extends Field
{
    /**
     * Render element value as logo image
     *
     * @param AbstractElement $element
     * @return string
     */

    protected function _getElementHtml(AbstractElement $element)
    {
        $html = '<div id="' . $element->getHtmlId() . '">';

        $options = $element->getValues();

        foreach ($options as $key => $option) {
        	if($key == 0) {
                $html .= '<label id="logoRadio">';
                $html .= '<input type="radio" id="'.'logoDisp_' . $option['value'] . '" value="' . $option['value'] . '">';
                $html .= '<img src="' . $this->getViewFileUrl('Tryspeed_BitcoinPayment::images/Logo.png') . '" alt="logo" style="height: 35px; width: 35px; position: relative; top: 9px; margin-right: 10px;">';
                $html .= '</label>';
            } else {
                $html .= '<label>';
                $html .= '<input type="radio" id="'.'logoDisp_' . $option['value'] . '" value="' . $option['value'] . '">';
                $html .= 'No Logo';
                $html .= '</label>';
	        }
        }

        $html .= '</div>';

        return $html;
    }
}
