<?php

namespace App\Services;

use App\Facades\NodeApi;
use App\Facades\NodeRepositoryFacade;
use Carbon\Carbon;

class NodeHelperService
{
    public function getBlockTransactions($currentBlockNumber)
    {
        //var_dump('call api: eth_getBlockByNumber()' . ' || ' . Carbon::now()->toDateTimeString());
        $currentBlock = NodeApi::eth_getBlockByNumber('0x' . dechex($currentBlockNumber), true);

        $transactions = [];

        if($currentBlock['status'] == 200) {
            $currentBlockTransactions = $currentBlock['body']['result']['transactions'];

            foreach ($currentBlockTransactions as $transaction) {
                // eth
                if($transaction['input'] == '0x') {
                    $data = [
                        'block'        => hexdec($transaction['blockNumber']),
                        'contract'     => 'eth',
                        'tx'           => $transaction['hash'],
                        'from'         => $transaction['from'],
                        'to'           => $transaction['to'],
                        'value'        => substr($transaction['value'], 2),
                    ];
                    $address = NodeRepositoryFacade::getRecord('Address', ['address' => $data['to']]);
                    if($address) {
                        array_push($transactions, $data);
                    }
                }
                // eth tokens
                else {
                    if(substr($transaction['input'], 0, 10) == '0xa9059cbb') {
                        $data = [
                            'block'        => hexdec($transaction['blockNumber']),
                            'contract'     => $transaction['to'],
                            'tx'           => $transaction['hash'],
                            'from'         => $transaction['from'],
                            'to'           => '0x' . ltrim(substr($transaction['input'], 10, 64), '0'),
                            'value'        => substr($transaction['input'], 74),
                        ];
                        $address = NodeRepositoryFacade::getRecord('Address', ['address' => $data['to']]);
                        if($address) {
                            array_push($transactions, $data);
                        }
                    }
                }
            }
        }
        return $transactions;
    }

    public function bcDecodeValue($hex, $decimal = 18)
    {
        $num = $this->bchexdec($hex,$decimal);
        $num = str_pad($num, $decimal, 0, STR_PAD_LEFT);

        $dec = substr($num, -$decimal);

        $int = substr($num, 0, -$decimal);

        $final = $int . '.' . $dec;

        $final = rtrim($final, "0");
        $final = rtrim($final, ".");

        if (substr($final, 0, 1) === '.') {
            return '0' . $final;
        }

        return $final;
    }

    private function bchexdec($hex,$decimal = 18)
    {
        $remainingDigits = substr($hex, 0, -1);
        $lastDigitToDecimal = \hexdec(substr($hex, -1));

        if (strlen($remainingDigits) === 0) {
            return $lastDigitToDecimal;
        }

        return addAmount(mulAmount(16, $this->bchexdec($remainingDigits,$decimal)), $lastDigitToDecimal, $decimal);
    }
}
