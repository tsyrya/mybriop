<?php
namespace app\models\polzovatel;

use app\behaviors\PolzovatelProperty;
use app\entities\Polzovatel;
use app\entities\settings\Masterparol;
use app\validators\LoginFilter;
use app\validators\LoginValidator;
use yii\base\Model;
use Yii;

/**
 * Class Vhod
 * @package app\models\polzovatel
 *
 * @property Polzovatel $polzovatel
 */
class Vhod extends Model
{
    public $login;
    public $parol;
    public $zapomnit;

    public function rules()
    {
        return [
            ['login', LoginFilter::className()],
            ['login', LoginValidator::className()],
            ['login', 'required'],
            ['login', 'validatePolzovatel'],

            ['parol', 'required'],
            ['parol', 'validateParol'],
            ['zapomnit', 'boolean']
        ];
    }

    public function behaviors()
    {
        return [PolzovatelProperty::className()];
    }

    public function validatePolzovatel($attribute)
    {
        if (!$this->polzovatel) {
            $this->addError($attribute, 'Пользователь не найден');
            return;
        }

        if ($this->isMasterparol())
            return;

        if (!$this->polzovatel->aktiven)
            $this->addError($attribute, 'Пользователь не прошёл активацию по e-mail или отключен');
    }

    public function attributeLabels()
    {
        return [
            'login' => 'Логин',
            'parol' => 'Пароль',
            'zapomnit' => 'Запомнить'
        ];
    }

    public function validateParol()
    {
        if ($this->isMasterparol())
            return;

        if (!$this->polzovatel)
            return;

        if (!$this->polzovatel->validateParol($this->parol))
            $this->addError('parol', 'неверный пароль');
    }

    public function login()
    {
        if (!$this->validate())
            return false;

        return Yii::$app->user->login(
            $this->polzovatel,
            $this->zapomnit ? 3600 * 24 * 7 : 0
        );
    }

    public function isMasterparol()
    {
        if ($this->_masterparol === null)
            $this->_masterparol = Masterparol::get();

        return $this->_masterparol->aktiven
            && $this->_masterparol->validateParol($this->parol);
    }

    /**
     * @var null|Masterparol
     */
    private $_masterparol;
}
