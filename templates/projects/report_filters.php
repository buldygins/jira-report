<div id="filters" class="container">
    <div class="row">
        <div class="col-9">
            <div class="form-group">
                <label class="form-check-label" for="user_filter">Сотрудник</label>
                <select class="form-control" id="user_filter">
                    <option value="-1" selected>Не выбрано</option>
                    <?php foreach ($users as $user_id => $user_name): ?>
                        <option value="<?= $user_id ?>"><?= $user_name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-check-label" for="project_filter">Проект</label>
                <select class="form-control" id="project_filter">
                    <option value="-1" selected>Не выбрано</option>
                    <?php foreach ($projects as $project_id => $project_name): ?>
                        <option value="<?= $project_id ?>"><?= $project_name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="daily_cost">
                <label class="form-check-label" for="daily_cost">Почасовой расчёт</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="all_tasks">
                <label class="form-check-label" for="all_tasks">Показать все задачи</label>
            </div>
            <div class="form-group">
                <label for="month">Месяц: </label>
                <input class="form-control" type="month" id="month" value="<?= date('Y-m') ?>"/>
            </div>
        </div>
        <div class="col-3">
            <button id="makeReport" class="btn btn-secondary">Показать по фильтрам</button>
        </div>
    </div>
</div>