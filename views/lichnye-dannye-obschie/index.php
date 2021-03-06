<?php
use app\models\lichnye_dannye_obschie\ObschieDannyeForm;
use app\widgets\DatePicker;
use app\widgets\InnInput;
use app\widgets\PasportKodPodrazdeleniyaInput;
use app\widgets\PasportNomerInput;
use app\widgets\SnilsInput;
use app\widgets\TelefonInput;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

/**
 * @var $model ObschieDannyeForm
 * @var $saved boolean Whether the model is saved
 */
?>
<?php $form = ActiveForm::begin(['layout' => 'horizontal']) ?>

<div class="row">
    <div class="col-md-6">
        <fieldset>
            <legend>Личные данные</legend>
            <?= $form->field($model, 'familiya') ?>
            <?= $form->field($model, 'imya') ?>
            <?= $form->field($model, 'otchestvo') ?>
            <?= $form->field($model, 'data_rozhdeniya')->widget(DatePicker::className()) ?>
        </fieldset>
    </div>

    <div class="col-md-6">
        <fieldset>
            <legend>Контакты</legend>
            <?= $form->field($model, 'telefon')->widget(TelefonInput::className()) ?>
            <?= $form->field($model, 'email') ?>
        </fieldset>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <fieldset>
            <legend>Паспорт</legend>
            <?= $form->field($model, 'pasport_no')->widget(PasportNomerInput::className()) ?>
            <?= $form->field($model, 'pasport_kem_vydan_kod')->widget(PasportKodPodrazdeleniyaInput::className()) ?>
            <?= $form->field($model, 'pasport_kem_vydan') ?>
            <?= $form->field($model, 'pasport_kogda_vydan')->widget(DatePicker::className()) ?>
        </fieldset>
    </div>

    <div class="col-md-6">
        <fieldset>
            <legend>Дополнительные данные</legend>
            <?= $form->field($model, 'inn')->widget(InnInput::className()) ?>
            <?= $form->field($model, 'snils')->widget(SnilsInput::className()) ?>
            <?= $form->field($model, 'propiska') ?>
        </fieldset>
    </div>
</div>

<?= Html::submitButton('Изменить', ['class' => 'btn btn-primary']) ?>

<?php ActiveForm::end() ?>
