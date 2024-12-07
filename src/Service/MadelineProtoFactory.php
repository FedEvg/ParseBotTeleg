<?php

namespace App\Service;

use danog\MadelineProto\API;
use danog\MadelineProto\Exception;

class MadelineProtoFactory
{
    /**
     * @throws Exception
     */
    public function create(string $session, MadelineProtoSettings $settings): API
    {
        try {
            $madelineProto = new API($session, $settings->getSettings());
            $madelineProto->start();

//            try {
//                $madelineProto->getSelf();
//            } catch (\danog\MadelineProto\Exception $e) {
//                $phoneNumber = '380966685158';
//                $madelineProto->phoneLogin($phoneNumber);
//
//                $authorization = $madelineProto->completePhoneLogin('Введите код из SMS:');  // Введите код вручную
//
//                if ($authorization['_'] === 'account.password') {
//                    $authorization = $madelineProto->complete2falogin('Введите пароль:');  // Введите пароль вручную
//                }
//
//            }

            return $madelineProto;
        } catch (\Exception $e) {
            throw new Exception("Error init MadelineProto: " . $e->getMessage());
        }
    }
}