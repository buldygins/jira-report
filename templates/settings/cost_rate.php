<form method="post" action="">
    <div class="form-group">
        <select class="form-control" name="entity_id" id="entity_id">
            <optgroup label="Сотрудники">
                <?php foreach ($users as $user):?>
                    <option value="U_<?= $user->id ?>"><?= $user->displayName ?></option>
                <?php endforeach; ?>
            </optgroup>
            <optgroup label="Проекты">
                <?php foreach ($desks as $desk): ?>
                    <option value="P_<?= $desk->id ?>"><?= $desk->jira_key ?></option>
                <?php endforeach; ?>
            </optgroup>
        </select>
    </div>
    <div class="form-group">
        <label>Ставка<input class="form-control" type="number" name="rate"></label>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="hourly"><label>Почасовая</label>
    </div>
    <input type="hidden" name="title" id="title">
    <input class="btn btn-primary" type="submit" value="Сохранить">
</form>