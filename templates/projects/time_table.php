<div class="pb-3 pt-3">
    <h3><?= $project_name ?></h3>
    <?php if (count($tasks)): ?>
        <table class="table table-bordered table-striped  table-hover table-sm">
            <thead>
            <th class="table-secondary" scope="col" colspan="2"></th>
            <th class="table-secondary" scope="col" colspan="1">Статус</th>
            <?php
            for ($i = 1; $i < $days; $i++):?>
                <th scope="col" class="table-secondary"><?= $i ?></th>
            <?php endfor; ?>
            <th scope="col" class="table-secondary">Всего</th>
            </thead>
            <tbody>
            <?php foreach ($tasks as $task)
            {
                $all_time = isset($time[$task->project_key]['all']) ? $time[$task->project_key]['all'] : 0;
                ?>
                <tr>
                    <td scope="row" class="table-warning">
                        <a href="<?= $host ?>/browse/<?= $task->project_key ?>" target="_blank"><nobr><?= $task->project_key ?></nobr></a>
                    </td>
                    <td scope="row" class="table-warning">
                        <nobr><?= $task->summary ?></nobr>
                    </td>
                    <td scope="row" class="table-warning">
                        <center>
                            <span style="padding: 4px;">
                                <nobr><?= $task->status ?></nobr>
                            </span>
                        </center>
                    </td>
                    <?php
                    for ($i = 1; $i < $days; $i++)
                    {
                        $is_weekend = (date('N', strtotime($date . $i)) >= 6);
                        $class = isset($time[$task->project_key][$i]) ? 'table-success' : 'table-secondary';
                        $class = $is_weekend ? 'weekend' : $class;
                        $display_time = isset($time[$task->project_key][$i]) ? $time[$task->project_key][$i] : '';
                            ?>
                        <td class="<?= $class ?>"><nobr><?= $display_time ?></nobr></td>

                    <?
                    }
                    ?>
                    <td class="table-<?= $all_time > 0 ? 'success' : 'secondary' ?>">
                        <?php  if ($all_time) echo '<nobr>'.$all_time.'</nobr>'; ?>
                    </td>
                </tr>
            <?php
            }
            ?>
            <tr>
                <td scope="row" class="table-secondary" colspan="3">Всего за день</td>
                <?php for ($i = 1; $i < $days; $i++)
                {
                    $is_weekend = (date('N', strtotime($date . $i)) >= 6);
                    $class = isset($time[$i]['all']) ? 'table-success' : 'table-secondary';
                    $class = $is_weekend ? 'weekend' : $class;
                    ?>
                    <td class="<?= $class ?>"><nobr><?= $time[$i]['all'] ?? '' ?></nobr></td>
                <?php
                }
                ?>
                <td scope="row" class="table-primary"><nobr><?= $time['all']; ?></nobr></td>
            </tr>
            </tbody>
        </table>
    <?php foreach ($costs as $cost): ?>
        <!--span>Расчёт: <?= $cost['calculated'] ?> руб. Ставка <?= $cost['rate'] ?>. <?= $cost['additional'] ?></span --><br>
    <?php endforeach; ?>
    <?php else : ?>
        <p>Задачи не найдены.</p>
    <?php endif; ?>
</div>
<hr style="height:2px;  border-width:0; background-color:black">
