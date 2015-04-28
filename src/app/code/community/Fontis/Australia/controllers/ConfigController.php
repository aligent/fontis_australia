<?php
/**
 * @package    Fontis_Australia
 */
class Fontis_Australia_ConfigController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Tax setup
     *
     * If triggered by a user, setups up AU-specific tax rules (removing the default tax rule in the process.
     */
    public function taxAction()
    {
        try {
            Mage::getModel('tax/calculation_rule')->load(2)->delete();
            $oRule = Mage::getModel('tax/calculation_rule')->load(1);  //load the default Rule created with the Mage install.
            $rateModel = Mage::getModel('tax/calculation_rate')->load('AU-*-*-GST', 'code');

            $rateData = array(
                'code' => 'AU-*-*-GST',
                'tax_country_id' => 'AU',
                'tax_region_id' => '*',
                'tax_postcode' => '*',
                'rate' => 10,
            );

            foreach ($rateData as $dataName => $dataValue) {
                $rateModel->setData($dataName, $dataValue);
            }

            $rateModel->save();

            $iGSTRateId = $rateModel->getId();
            $oRule->setTaxRate(array($iGSTRateId))//note the single element array
            ->setTaxCustomerClass(array(3))//hard-coded to default retail customer tax class
            ->setTaxProductClass(array(2))//hard-coded to default product Taxable class
            ->save();

            //Configuration values:
            $config = Mage::getSingleton('core/config');
            $config->saveConfig('general/country/default', 'AU');
            $config->saveConfig('general/country/allow', 'AU');

            // Configuration / Currency Setup
            $config->saveConfig('currency/options/base', 'AUD');
            $config->saveConfig('currency/options/default', 'AUD');
            $config->saveConfig('currency/options/allow', 'AUD');

            // Configuration / Shipping Settings
            $config->saveConfig('shipping/origin/country_id', 'AU');

            // Configuration / Tax
            $config->saveConfig('tax/classes/shipping_tax_class', 2);

            $config->saveConfig('tax/calculation/price_includes_tax', 1);
            $config->saveConfig('tax/calculation/shipping_includes_tax', 1);
            $config->saveConfig('tax/calculation/apply_after_discount', 1);
            $config->saveConfig('tax/calculation/discount_tax', 1);
            $config->saveConfig('tax/calculation/apply_tax_on', 0);

            $config->saveConfig('tax/defaults/country', 'AU');

            $config->saveConfig('tax/display/type', 2);
            $config->saveConfig('tax/display/shipping', 2);

            $config->saveConfig('tax/cart_display/price', 2);
            $config->saveConfig('tax/cart_display/subtotal', 2);
            $config->saveConfig('tax/cart_display/shipping', 2);

            $config->saveConfig('tax/sales_display/price', 2);
            $config->saveConfig('tax/sales_display/subtotal', 2);
            $config->saveConfig('tax/sales_display/shipping', 2);

            ////  delete other incorrect tax Rates:
            $aRates = Mage::getModel('tax/calculation_rate')->getCollection();
            foreach ($aRates as $oRate) {
                if ($oRate->getCode() != 'AU-*-*-GST') {
                    Mage::getModel('tax/calculation')
                        ->getCollection()
                        ->addFieldToFilter('tax_calculation_rate_id', array(
                            'eq' => $oRate->getId()
                        ))
                        ->walk(function ($item) {
                            $item->delete();
                        });
                    $oRate->delete();
                }
            }
            Mage::app()->getResponse()->setHttpResponseCode(200);
            Mage::app()->getResponse()->setBody('1');
        } catch (Exception $ex) {
            Mage::app()->getResponse()->setBody('0');
        }
    }
}
