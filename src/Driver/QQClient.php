<?php

namespace FeiBam\Seat\Connector\Drivers\QQ\Driver;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Seat\Services\Exceptions\SettingException;
use Warlof\Seat\Connector\Drivers\IClient;
use Warlof\Seat\Connector\Drivers\ISet;
use Warlof\Seat\Connector\Drivers\IUser;
use Warlof\Seat\Connector\Exceptions\DriverException;
use Warlof\Seat\Connector\Exceptions\DriverSettingsException;
use Warlof\Seat\Connector\Exceptions\InvalidDriverIdentityException;
use Warlof\Seat\Connector\Models\User;


class QQClient implements IClient {
    private static $instance;

    private $members;

    private function __construct(){
        $this->members = collect();
    }

    public static function getInstance() : IClient{
        if (! isset(self::$instance)){
            try {
                $settings = setting('seat-connector.drivers.QQ', true);
            } catch (SettingException $e) {
                throw new DriverException($e->getMessage(), $e->getCode(), $e);
            }
            if (is_null($settings) || ! is_object($settings))
                throw new DriverSettingsException('The Driver has not been configured yet.');
            self::$instance = new QQClient();
        }
        return self::$instance;
    }

    public function getUsers(): array
    {
        if (!$this->members->isEmpty()) {
            return $this->members->all();
        }

        $users = User::where('connector_type', 'qq')->get();
        foreach ($users as $user) {
            $qq_member = new QQMember($user);
            $this->members->put($qq_member->user_id, $qq_member);
        }

        return $this->members->all();
    }

    public function getSets() : array {
        return [];
    }

    public function getUser(string $id) : IUser {
        $member = $this->members->get($id);

        if (! is_null($member))
            return $member;

        $member = User::where('connector_type', 'qq')
        ->where('user_id', auth()->user()->id)
        ->first();

        if(is_null($member)){
            return null;
        }

        $member = new QQMember($member);

        $this->members->put($member->user_id,$member);

        return $member;
    }

    public function getSet(string $id) : ISet {
        return new QQSet();
    }
}