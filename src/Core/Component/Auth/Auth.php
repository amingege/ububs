<?php
namespace FwSwoole\Component\Auth;

use FwSwoole\Core\Factory;
use FwSwoole\Core\Request;
use FwSwoole\Core\Tool\Config;
use FwSwoole\Log\Log;
use FwSwoole\Middleware\Adapter\JWTAuth;

class Auth extends Factory
{
    private static $type        = '';
    private static $authConfigs = [];
    private static $class       = '';
    private $userPrimaryValue   = '';

    /**
     * 容器注入
     * @param  string $type config.auth配置
     * @return object
     */
    public static function guard($type = 'web')
    {
        $config = Config::get('auth.guards');
        if (!isset($config[$type])) {
            throw new \Exception("Error not exists auth.{$type}");
        }
        self::$type        = $type;
        self::$authConfigs = $config[$type];
        self::$class       = self::$authConfigs['provider'];
        return self::getInstance();
    }

    /**
     * 登录
     * @param  array  $loginData 登录数据
     * @param  boolean $remember  是否记住密码
     * @return array
     */
    public function attempt(array $loginData, $remember)
    {
        if (count($loginData) !== 2) {
            return false;
        }
        $usernameField  = self::$authConfigs['username'] ?? 'username';
        $passwordField  = self::$authConfigs['password'] ?? 'password';
        $whereParams = [
            $usernameField => $loginData[$usernameField],
            $passwordField => generatePassword($loginData[$passwordField]),
        ];
        $result['list'] = self::$class::getDB()->where($whereParams)->first();
        if (empty($result['list'])) {
            return false;
        }
        // 生成一个token
        $result['__TOKEN__'] = JWTAuth::getInstance()->createToken($result['list'][self::$class::primaryKey()]);
        return $result;
    }

    public function validateAuth()
    {
        return JWTAuth::getInstance()->attempt(Request::getAuthorization());
    }

    /**
     * 检测是否已经授权登录
     * @param  string $token jwt
     * @return boolean
     */
    public function check()
    {
        $userPrimaryValue = $this->validateAuth();
        if (!$userPrimaryValue) {
            return false;
        }
        $this->userPrimaryValue = $userPrimaryValue;
        return true;
    }

    public function logout()
    {
        return true;
    }

    /**
     * 获取当前登录的用户
     * @return array
     */
    public function user()
    {
        if (!$this->userPrimaryValue) {
            $userPrimaryValue = $this->validateAuth();
            if (!$userPrimaryValue) {
                Log::info('no login');
                return [];
            }
            $this->userPrimaryValue = $userPrimaryValue;
        }
        return self::$class::getDB()->find($this->userPrimaryValue);
    }

    /**
     * 获取当前登录的主键
     * @return string
     */
    public function id()
    {
        if (!$this->userPrimaryValue) {
            $userPrimaryValue = $this->validateAuth();
            if (!$userPrimaryValue) {
                Log::info('no login');
                return [];
            }
            $this->userPrimaryValue = $userPrimaryValue;
        }
        return $this->userPrimaryValue;
    }
}
