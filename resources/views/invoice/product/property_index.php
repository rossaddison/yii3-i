<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

echo "<tbody>";
echo "<tr>";
foreach ($all as $product_property) { 
echo "<td>". Html::a($product_property->getName(),$urlGenerator->generate('productproperty/view',['id'=>$product_property->getProperty_id()])). "</td>";
echo "<td>". $product_property->getValue().  "</td>";
}; 
echo "</tr>";
echo "</tbody>";    
 



