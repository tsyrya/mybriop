<?php

use \kartik\widgets\Select2;
use \app\entities\AdresnyjObjekt;
use \app\entities\Vedomstvo;
use kartik\widgets\DepDrop;
use yii\widgets\ActiveForm;
use \app\entities\EntityQuery;
use yii\helpers\Html;
use yii\helpers\Url;
use \app\entities\Dolzhnost;
use \app\enums\EtapObrazovaniya;
use \Yii;
use app\widgets\Select3;



$form = ActiveForm::begin([
    'id'=>'dolzhnostForm',
    'options' => ['tabindex'=>false]
]);

if ($list){
    echo '<h2>Добавить новую должность</h2>';
}

echo $form->field($model,'fizLicoId')->hiddenInput()->label(false);

if (isset($zayavlenie)){
    echo '<input name="zayavlenie" type="hidden" value="'.$zayavlenie.'">';
}

echo $form->field($model,'organizaciyaAdress')->widget(Select2::className(),[
    'data'=>AdresnyjObjekt::findBurRajon()->commonOnly()->formattedAll(EntityQuery::DROP_DOWN, 'formalnoeNazvanie'),
    'options'=>['placeholder'=>'Выберите район / город']
]);

echo $form->field($model, 'organizaciyaVedomstvo')->widget(Select2::classname(), [
    'data' => Vedomstvo::find()->formattedAll(EntityQuery::DROP_DOWN, 'nazvanie'),
    'options' => ['placeholder' => 'Выберите ведомство'],
]);

echo '<label>Организация</label>';

echo Html::hiddenInput('',$model->organizaciyaId,['id'=>'organizaciyaIdHiddenInput']);

$depdropUrl ='/attestaciya/rabota-org'.($model->organizaciyaId ? '?oid='.$model->organizaciyaId : '');

echo $form->field($model, 'organizaciyaId')->widget(DepDrop::classname(), [
    'type' => DepDrop::TYPE_SELECT2,
    'pluginOptions'=>[
        'depends' => [
            Html::getInputId($model, 'organizaciyaVedomstvo'),
            Html::getInputId($model, 'organizaciyaAdress')
        ],
        'placeholder' => 'Выберите школу',
        'url' => Url::to([$depdropUrl]),
        'initialize'=>true,
        'allowClear' => true
    ],
    'pluginEvents'=>[
        'change' => 'function (){
            var organizaciyaIdInput = \''.Html::getInputId($model,'organizaciyaId').'\';
            var organizaciyaNazvanieInput = \''.Html::getInputId($model,'organizaciyaNazvanie').'\';
            if ($(\'#\'+organizaciyaIdInput).val()){
                $(\'#\'+organizaciyaNazvanieInput).val("");
            }
        }'
    ]
])->label(false);

echo '<p>Не нашли организацию в списке? <span class="slink" onclick="showOrganizaciyaNazvanie(\''.Html::getInputId($model,'organizaciyaNazvanie').'\')">ввести наименование организации вручную</span></p>';

echo $form->field($model,'organizaciyaNazvanie')
    ->input('text',[
        'class'=>'hidden form-control',
        'placeholder'=>'Наименование организации',
        'onkeyup' => 'onOrganizaciyaNazvanieKeyUp(\''.Html::getInputId($model,'organizaciyaId').'\',this.value,event)'
    ])
    ->label(false);

//echo $form->field($model,'dolzhnostId')->widget(Select2::className(),[
//    'data' => Dolzhnost::getObshieDolzhnosti()->orderBy('nazvanie')->formattedAll(EntityQuery::DROP_DOWN, 'nazvanie'),
//    'options' => ['placeholder' => 'Выберите должность'],
//]);

echo Select3::widget([
    'model' => $model,
    'attribute' => "dolzhnostId",
    'secondAttribute' => "dolzhnostNazvanie",
    'data' => Dolzhnost::getObshieDolzhnosti()->orderBy('nazvanie')->formattedAll(EntityQuery::DROP_DOWN, 'nazvanie'),
    'placeholder' => 'Выберите должность',
    'secondPlaceholder' => 'Введите наименование должности'
]);

//dolzhnostNazvanie
echo $form->field($model, 'etapObrazovaniya')->widget(Select2::className(), [
    'data' => EtapObrazovaniya::namesMap(),
    'options' => ['placeholder' => 'Выберите уровень образования']
]);

if (!$list) {
    echo '<button class="btn btn-default" onclick="close_modal()">Закрыть</button>';
}
echo ' <button class="btn btn-primary">Сохранить</button>';

ActiveForm::end();

?>
<script type="text/javascript">
    
    function showOrganizaciyaNazvanie(organizaciyaInputId){
        $('#'+organizaciyaInputId).toggleClass('hidden');
    }

    function onOrganizaciyaNazvanieKeyUp(organizaciyaIdInput,value,event){
        if (value != '')
            $('#'+organizaciyaIdInput).select2('val','');
    }

    function onOrganizaciyaIdChange(organizaciyaIdInput, organizaciyaNazvanieInput){
        if ($('#'+organizaciyaIdInput).val()){
            $('#'+organizaciyaNazvanieInput).val('');
        }
    }

</script>