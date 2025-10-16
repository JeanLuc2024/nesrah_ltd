<?php
/**
 * Format a number as Rwandan Francs (Rwf) with 2 decimal places
 * 
 * @param float $amount The amount to format
 * @return string Formatted currency string with 2 decimal places
 */
function formatCurrencyWithDecimals($amount) {
    return 'Rwf ' . number_format($amount, 2, '.', ',');
}
?>
