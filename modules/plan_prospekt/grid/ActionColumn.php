<?php
namespace app\modules\plan_prospekt\grid;

use app\enums2\TipKursa;
use app\records\Kurs;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use Yii;

use app\modules\plan_prospekt\models\KursForm;

class ActionColumn extends \yii\grid\ActionColumn
{
    public $template = '{update} {delete} {iup}';

    public function init()
    {
        parent::init();

        if (!isset($this->buttonOptions['class']))
            $this->buttonOptions['class'] = ['btn', 'btn-default', 'btn-action'];

        if (!isset($this->header)) {
            $url = $this->createUrl('create', null, null, null);
            $this->header = $this->createButton($url, 'Создать', ['btn-create']);
        }

        /**
         * @param Kurs $model
         * @return bool
         */
        $this->visibleButtons['iup'] = function ($model) {
            return $model->tip === TipKursa::PP || $model->tip === TipKursa::PO;
        };
    }

    /**
     * Initializes the default button rendering callbacks.
     */
    protected function initDefaultButtons()
    {
        if (!isset($this->buttons['update'])) {
            $this->buttons['update'] = function ($url) {
                return $this->createButton($url, 'Редактировать', ['btn-update']);
            };
        }
        if (!isset($this->buttons['delete'])) {
            $this->buttons['delete'] = function ($url) {
                return $this->createButton($url, 'Удалить', ['btn-delete']);
            };
        }
        if (!isset($this->buttons['iup'])) {
            $this->buttons['iup'] = function ($url, $model) {
                /* @var $model KursForm */
                $text = $model->iup ? 'Отменить ИУП' : 'Назначить ИУП';
                return $this->createButton($url, $text, ['btn-iup']);
            };
        }
    }

    private function createButton($url, $text, $class)
    {
        $options = ArrayHelper::merge([
            'title' => $text,
            'aria-label' => $text,
            'class' => $class,
            'data-pjax' => 0,
        ], $this->buttonOptions);

        return Html::a($text, $url, $options);
    }
}