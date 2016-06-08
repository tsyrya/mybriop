<?php
    use \app\enums\KategoriyaPedRabotnika;

    $number = 1;
    $current_kategoriya = '';
?>

<table class="tb">
    <tr>
        <td>№</td>
        <td>ФИО</td>
        <td>ОУ</td>
        <td>Должность</td>
        <td>Год рожд.</td>
        <td>Имеющаяся кв. кат.</td>
        <td>Стаж пед./в учр./в долж.</td>
        <td>Образование</td>
        <td>Повышение квалификации</td>
        <td>Рез-ты кв. экз.</td>
        <td>Портфолио</td>
        <td>СПД</td>
        <td>Экспертное заключение</td>
    </tr>
    <?php foreach ($data as $key => $items) {?>
    <?if ($current_kategoriya != $key):?>
            <tr>
                <td colspan="13" class="center">
                    <?
                        if ($key == 'otraslevoe_soglashenie'){
                                echo 'Высшая категория (по отраслевому соглашению)';
                        }
                        else {
                            echo \app\globals\ApiGlobals::first_letter_up(KategoriyaPedRabotnika::namesMap()[$key]);
                        }
                    ?>
                </td>
            </tr>
    <?
            $current_kategoriya = $key;
            $number = 1;
    ?>
    <?endif?>
    <? foreach ($items as $item) {?>
    <tr>
        <td><?=$number?></td>
        <td><?=$item['fio']?></td>
        <td><?=$item['organizaciya']?></td>
        <td><?=$item['dolzhnost']?></td>
        <td><?=$item['god_rozhdeniya']?></td>
        <td><?=KategoriyaPedRabotnika::namesMap()[$item['imeushayasya_kategoriya']].', '.date('d.m.Y',strtotime($item['attestaciya_data_prisvoeniya']))?></td>
        <td><?=$item['ped_stazh']?>/<?=$item['rabota_stazh_v_dolzhnosti']?>/<?=$item['stazh_v_dolzhnosti']?></td>
        <td><?=$item['obrazovanie']?></td>
        <td><?=$item['kursy']?></td>
        <td>
            <?
                if ($item['na_kategoriyu'] == KategoriyaPedRabotnika::PERVAYA_KATEGORIYA) {
                    echo 'Не предусмотрена';
                }
                else{
                    if ($item['otraslevoe_soglashenie']){
                        echo $item['otraslevoe_soglashenie'];
                    }
                    else{
                        echo $item['variativnoe_ispytanie_3'];
                    }
                }
            ?>
        </td>
        <td><?=$item['portfolio']?></td>
        <td><?= ($item['na_kategoriyu'] == KategoriyaPedRabotnika::PERVAYA_KATEGORIYA or $item['otraslevoe_soglashenie'])
                ? 'Не предусмотрена'
                : $item['spd']?></td>
        <td></td>
    </tr>
    <?
            $number++;
        }
      }
    ?>
</table>