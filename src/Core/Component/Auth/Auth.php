<?php
namespace Ububs\Core\Component\Auth;

use Ububs\Core\Http\Interaction\Request;
use Ububs\Core\Component\Db\Db;
use Ububs\Core\Component\Factory;
use Ububs\Core\Component\Middleware\Adapter\JWTAuth;

class Auth extends Factory
{
    private static $table = null;
    private static $time  = null;

    /**
     * 指定登录表
     * @param  string $table 表名
     * @return object
     */
    public static function guard($table = 'user')
    {
        // 阻塞
        if (self::$table === null || self::$time === null || self::$time < time() - 2) {
            self::$table = $table;
            self::$time  = time();
            return self::getInstance();
        }
    }

    /**
     * 验证登录信息
     * @param  array  $loginData 登录数据
     * @param  boolean $remember  是否记住密码
     * @return array
     */
    public function attempt(array $loginData, $remember)
    {
        $result = [];
        if (count($loginData) !== 2) {
            self::$table = null;
            self::$time  = null;
            return $result;
        }
        $configs  = isset(config('auth.guards')[self::$table]) ? config('auth.guards')[self::$table] : [];
        $account  = $configs['account'] ?? 'account';
        $password = $configs['password'] ?? 'password';
        if (!isset($loginData[$account]) || !isset($loginData[$password])) {
            self::$table = null;
            self::$time  = null;
            return $result;
        }
        $wheres = [
            $account  => $loginData[$account],
            $password => generatePassword($loginData[$password]),
        ];
        $list       = Db::table(self::$table)->where($wheres)->first();
        if (empty($list)) {
            self::$table = null;
            self::$time  = null;
            return $result;
        }
        $tableData  = Db::query('describe ' . self::$table);
        $primaryKey = 'id';
        foreach ($tableData as $fieldData) {
            if ($fieldData['Key'] == 'PRI') {
                $primaryKey = $fieldData['Field'];
                break;
            }
        }
        // 生成一个token
        $result['__UBUBS_TOKEN__'] = JWTAuth::getInstance()->createToken($list[$primaryKey], self::$table);
        $result['list']            = $list;
        self::$table               = null;
        self::$time                = null;
        return $result;
    }

    /**
     * 检测是否已经授权登录
     * @param  string $token jwt
     * @return boolean
     */
    public static function check()
    {
        return JWTAuth::getInstance()->attempt(Request::getAuthorization());
    }

    public function logout()
    {
        return true;
    }

    /**
     * 获取当前登录的用户
     * @return array
     */
    public static function user()
    {
        return JWTAuth::getInstance()->user();
    }

    /**
     * 获取当前登录的主键
     * @return string
     */
    public static function id()
    {
        return JWTAuth::getInstance()->id();
    }
}
