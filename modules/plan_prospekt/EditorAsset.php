<?php
namespace app\modules\plan_prospekt;

use yii\web\AssetBundle;

class EditorAsset extends AssetBundle
{
    public $sourcePath = '@app/modules/plan_prospekt/assets';

    public $js = [
        'mybriop.planProspektEditor.js'
    ];

    public $depends = [
        'yii\widgets\PjaxAsset'
    ];
}