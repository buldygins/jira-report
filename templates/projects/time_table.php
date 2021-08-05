<div class="pb-3 pt-3">
    <h3><?= $project_name ?></h3>
    <?php if (count($tasks)): ?>
        <table class="table table-bordered table-striped  table-hover table-sm">
            <thead>
            <th class="table-secondary" scope="col"></th>
            <?php
            for ($i = 1; $i < $days; $i++):?>
                <th scope="col" class="table-secondary"><?= $i ?></th>
            <?php endfor; ?>
            <th scope="col" class="table-secondary">Всего</th>
            </thead>
            <tbody>
            <?php foreach ($tasks as $task):
                $all_time = isset($time[$task->project_key]['all']) ? $time[$task->project_key]['all'] : 0;
                ?>
                <tr>
                    <td scope="row" class="table-warning"><?= $task->project_key ?> <?= $task->summary ?></td>
                    <?php for ($i = 1; $i < $days; $i++):
                        $is_weekend = (date('N', strtotime($date . $i)) >= 6);
                        $class = isset($time[$task->project_key][$i]) ? 'table-success' : 'table-secondary';
                        $class = $is_weekend ? 'weekend' : $class;
                        $display_time = isset($time[$task->project_key][$i]) ? $time[$task->project_key][$i] : '';
                            ?>
                            <td class="<?= $class ?>"><?= $display_time ?></td>
                        <?php endfor; ?>
                    <td class="table-<?= $all_time > 0 ? 'success' : 'secondary' ?>"><?php  if ($all_time) echo $all_time; ?></td>
                </tr>
            <?php
            endforeach;
            ?>
            <tr>
                <td scope="row" class="table-secondary">Всего за день</td>
                <?php for ($i = 1; $i < $days; $i++):
                    $is_weekend = (date('N', strtotime($date . $i)) >= 6);
                    $class = isset($time[$i]['all']) ? 'table-success' : 'table-secondary';
                    $class = $is_weekend ? 'weekend' : $class;
                    ?>
                    <td class="<?= $class ?>"><?= $time[$i]['all'] ?? '' ?></td>
                <?php endfor; ?>
            </tr>
            </tbody>
        </table>
    <?php foreach ($costs as $cost): ?>
        <span>Расчёт: <?= $cost['calculated'] ?> руб. Ставка <?= $cost['rate'] ?>. <?= $cost['additional'] ?></span><br>
    <?php endforeach; ?>
    <?php else : ?>
        <p>Задачи не найдены.</p>
    <?php endif; ?>
</div>
<hr style="height:2px;  border-width:0; background-color:black">
