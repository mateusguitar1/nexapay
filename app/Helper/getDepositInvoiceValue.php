<?php

function getDepositInvoiceValue($array_transaction = array())
{
    $deposit = 0;
    $deposit_count = 0;
    $total = 0;
    foreach($array_transaction as $transaction){
        if($transaction->type_transaction == 'deposit'){
            $total++;
            if($transaction->method_transaction == 'invoice'){
                $deposit_count++;

                if($transaction->status != 'pending'){
                    $deposit += $transaction->amount_confirmed;
                }else{
                    $deposit += $transaction->amount_solicitation;
                }
            }
        }
    }
    if($total != 0){
        $percent = ($deposit_count/$total)*100;
    }else{
        $percent = 0;
    }

    return $deposit_count.' R$ '.doubletostrH($deposit).' | '.doubletostrH($percent).'%';
}
