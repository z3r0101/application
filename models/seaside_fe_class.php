<?php

class seaside_fe_class extends Model
{
    function __construct()
    {
        parent::__construct();
        $this->dbClass->cmsDatabaseClass(0);
    }

    function compute_cart($pMarketId)
    {
        global $CONFIG;

        $subTotal = 0;
        $paymentTotal = 0;
        $cartTotal = 0;

        if (isset($_SESSION[$CONFIG['session']['prefix'] . "_user"]["cart"]["market"]['m_' . $pMarketId]['item'])) {
            foreach ($_SESSION[$CONFIG['session']['prefix'] . "_user"]["cart"]["market"]['m_' . $pMarketId]['item'] as $itemIndex => $itemData) {
                $arrData = $this->dbClass->select(
                    sprintf(
                        "SELECT * FROM seaside_item WHERE SEASIDE_Item_Id = %d",
                        $this->dbClass->mysqli->real_escape_string(intval($itemData['item_id']))
                    )
                );
                if (count($arrData) > 0) {
                    $itemAmount = 0;
                    $arrPriceList = json_decode($arrData[0]["SEASIDE_Item_Price"], true);
                    $arrPriceSel = array_filter($arrPriceList,
                        function ($item) use ($itemData) {
                            return ($itemData['qty'] == $item['unit_measure']);
                        }
                    );
                    if (count($arrPriceSel) > 0) {
                        $itemAmount = $itemData['price'];

                    } else {
                        $itemAmount = ($itemData['qty'] * $itemData['price']);
                    }
                    $subTotal += $itemAmount;

                    if (isset($itemData['options']) && count($itemData['options']) > 0) {
                        $arrOption = array_filter($itemData['options'],
                            function ($item) {
                                return ($item['type'] == 1);
                            }
                        );
                        foreach ($arrOption as $optionIndex => $optionData) {
                            $subTotal += $optionData['price'];
                        }
                    }
                }
            }


            if (isset($_SESSION[$CONFIG['session']['prefix'] . "_user"]["cart"]["market"]['m_' . $pMarketId]['payment'])) {
                $paymentTotal = array_reduce($_SESSION[$CONFIG['session']['prefix'] . "_user"]["cart"]["market"]['m_' . $pMarketId]['payment'], function (&$res, $item) {
                    return $res + ($item['amount']);
                }, 0);
            }

            $cartTotal = $subTotal + $paymentTotal;
        }

        return array($subTotal, $cartTotal);
    }

    function update_cart($pMarketId)
    {
        global $CONFIG;

        if (isset($_SESSION[$CONFIG['session']['prefix'] . "_user"]["cart"]["market"]['m_' . $pMarketId]['item'])) {
            foreach ($_SESSION[$CONFIG['session']['prefix'] . "_user"]["cart"]["market"]['m_' . $pMarketId]['item'] as $itemIndex => $itemData) {
                $arrData = $this->dbClass->select(
                    sprintf(
                        "SELECT * FROM seaside_item WHERE SEASIDE_Item_Id = %d",
                        $this->dbClass->mysqli->real_escape_string(intval($itemData['item_id']))
                    )
                );
                if (count($arrData) > 0) {
                    $itemAmount = 0;
                    $arrPriceList = json_decode($arrData[0]["SEASIDE_Item_Price"], true);
                    $arrPriceSel = array_filter($arrPriceList,
                        function ($item) use ($itemData) {
                            return ($itemData['qty'] == $item['unit_measure']);
                        }
                    );
                    if (count($arrPriceSel) > 0) {
                        $itemAmount = $itemData['price'];

                    } else {
                        $itemAmount = ($itemData['qty'] * $itemData['price']);
                    }

                    $optionAmount = 0;
                    if (isset($itemData['options']) && count($itemData['options']) > 0) {
                        $arrOption = array_filter($itemData['options'],
                            function ($item) {
                                return ($item['type'] == 1);
                            }
                        );
                        foreach ($arrOption as $optionIndex => $optionData) {
                            $optionAmount += $optionData['price'];
                        }
                    }

                    $_SESSION[$CONFIG['session']['prefix'] . "_user"]["cart"]["market"]['m_' . $pMarketId]['item'][$itemIndex]['amount'] = ($itemAmount + $optionAmount);
                }
            }
        }
    }
}