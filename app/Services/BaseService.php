<?php

namespace App\Services;


use Illuminate\Support\Facades\Auth;

abstract class BaseService
{
    protected function getCurrentUser()
    {
        return Auth::guard('api')->user();
    }

    protected function createServiceException($message = 'Service Exception', $code = 0)
    {
        return new \Exception($message, $code);
    }

    protected function createAccessDeniedException($message = 'Access Denied', $code = 0)
    {
        return new \Exception($message, $code);
    }

    protected function createNotFoundException($message = 'Not Found', $code = 0)
    {
        return new \Exception($message, $code);
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////
    // 设置答案
    protected function baseSetAnswer($answerList, $index, $answer, $time, $score, $result){
        $key =  $this->baseAnswerKey($index);
        $answerList[$key] = [
            'answer' => $answer,
            'time' => $time,
            'score' => $score,
            'result' => $result
        ];

        return  $answerList;
    }

    // 获得答案
    protected function baseGetAnswer($answerList, $index){
        $key = $this->baseAnswerKey($index);


        return  isset($answerList[$key]) ? $answerList[$key] :  null;
    }

    private function baseAnswerKey($index){
        return  't'.$index;
    }
    
    ///////////////////////////////////////////////////////////////////////////////////////////////////
    // 处理礼包的公共函数
    protected function dealWithPackage($userId, $packageInfo, $times=1){
        // 共支持3种，金币、现金、邀请券
        $coin_income = 0;
        $cash_income = 0;
        $ticket_income = 0;
        $drawticket_income = 0;

        if($packageInfo['type'] === config('game.packages.type.coin')){
            $coin_income += $this->doCoin($userId, $packageInfo, $times);
        } else if($packageInfo['type'] === config('game.packages.type.cash')){
            $cash_income += $this->doCash($userId, $packageInfo);
        } else if($packageInfo['type'] === config('game.packages.type.ticket')){
            $ticket_income += $this->doTicket($userId, $packageInfo);
        } else if($packageInfo['type'] === config('game.packages.type.drawticket')){
            $drawticket_income += $this->doDrawTicket($userId, $packageInfo);
        }

        return [
            'coin' => $coin_income,
            'cash' => $cash_income,
            'ticket' => $ticket_income,
            'drawticket' => $drawticket_income
        ];
    }

    // 处理现金TODO
    protected function doCash($userId, $packageInfo){
        $cashInfo = $packageInfo['content']['cash'];
        $cash_income = rand($cashInfo['min'], $cashInfo['max']);

        return $cash_income;
    }

    // 处理金币
    protected function doCoin($userId, $packageInfo, $times){
        $maxDragon = $this->getGateService()->getMaxCoin($userId);
        // $coin = 1000;

        $coin_income = floor($maxDragon['coin'] * ($packageInfo['content']['coin']['pct'] / 100) * $times);
        // $coin_income = floor($coin * ($packageInfo['content']['coin']['pct'] / 100) * $times);

        return $coin_income;
    }

    // 处理邀请券
    protected function doTicket($userId, $packageInfo){
        $ticket_income = $packageInfo['content']['ticket'];

        return $ticket_income;
    }    

    // 处理转盘券
    protected function doDrawTicket($userId, $packageInfo){
        $ticket_income = $packageInfo['content']['ticket'];

        return $ticket_income;
    }    

    // 添加随机文字
    protected function randomChar(){
        // 使用chr()函数拼接双字节汉字，前一个chr()为高位字节，后一个为低位字节
        $a = chr(mt_rand(0xB0,0xD0)).chr(mt_rand(0xA1, 0xF0));
        // 转码
        return iconv('GB2312', 'UTF-8', $a);
    }
}