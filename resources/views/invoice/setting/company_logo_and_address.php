<?php 
declare(strict_types=1);

use Yiisoft\Html\Html;
?>
<div style="width:100%;height:175px;overflow:auto;">
    <table style="width:100%">
        <tr> 
            <td style="width:60%;text-align:left">
                <div id="logo">
                    <img src="<?= '/'. $s->public_logo(); ?>" height="100" width="150"/>
                </div>
            </td>
            <td style="width:40%;text-align:left">
                <?php 
                    echo '<div><b>'.Html::encode($company['name']).'</b></div>';
                    echo '<div><br></div>';
                    echo '<div>' . $s->trans('vat_id_short') . ': ' . Html::encode($company['vat_id']) . '</div>';
                    echo '<div>' . $s->trans('tax_code_short') . ': ' . Html::encode($company['tax_code']) . '</div>';
                    echo '<div><br></div>';
                    echo '<div>' . Html::encode($company['address_1'] ? $s->trans('street_address') .': '. $company['address_1'] : ''). '</div>';
                    echo '<div>' . Html::encode($company['address_2'] ? $s->trans('street_address_2') .': '. $company['address_2'] : ''). '</div>';
                    echo '<div>' . Html::encode($company['city'] ? $s->trans('city') .': '. $company['city'] : ''). '</div>';
                    echo '<div>' . Html::encode($company['state'] ? $s->trans('state') .': '. $company['state'] : ''). '</div>';
                    echo '<div>' . Html::encode($company['zip'] ? $s->trans('zip') .': '. $company['zip'] : ''). '</div>';
                    echo '</div>';
                    echo '<div>' . $countryhelper->get_country_name($s->trans('cldr'), $company['country'] ?? 'United Kingdom') . '</div>';
                    echo '<br/>';
                    echo '<div>' .$s->trans('phone_abbr') . ': ' . Html::encode($company['phone'] ?? '') . '</div>';
                    echo '<div>' .$s->trans('fax_abbr') . ': ' . Html::encode($company['fax'] ?? '') . '</div>';
                ?>
            </td>
        </tr>
    </table>    
</div>