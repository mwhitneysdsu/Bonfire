<?php

$delete_icon = '<span class="icon-trash icon-white"></span>&nbsp;';
$empty_table = true;

?>
<style>
.media-box {
    margin-bottom: 20px;
}
</style>
<div class="row">
	<?php if (has_permission('Activities.Own.View')) : ?>
	<div class="column size1of4 media-box">
        <?php echo anchor(site_url(SITE_AREA . '/reports/activities/activity_own'), Assets::Image(Template::theme_url('images/activity-user.png'), array(), true)); ?>
		<p><strong><?php echo lang('activity_own'); ?></strong><br/>
    		<span><?php echo lang('activity_own_description'); ?></span>
		</p>
	</div>
	<?php
    endif;
    if (has_permission('Activities.User.View')) :
    ?>
	<div class="column size1of4  media-box">
		<?php echo anchor(site_url(SITE_AREA . '/reports/activities/activity_user'), Assets::Image(Template::theme_url('images/customers.png'), array(), true)); ?>
		<p><strong><?php echo lang('activity_users'); ?></strong><br/>
            <span><?php echo lang('activity_users_description'); ?></span>
		</p>
	</div>
	<?php
    endif;
    if (has_permission('Activities.Module.View')) :
    ?>
	<div class="column size1of4  media-box">
		<?php echo anchor(site_url(SITE_AREA . '/reports/activities/activity_module'), Assets::Image(Template::theme_url('images/product.png'), array(), true)); ?>
		<p><strong><?php echo lang('activity_modules'); ?></strong><br/>
    		<span><?php echo lang('activity_module_description'); ?></span>
		</p>
	</div>
	<?php
    endif;
    if (has_permission('Activities.Date.View')) :
    ?>
	<div class="column size1of4 media-box">
		<?php echo anchor(site_url(SITE_AREA . '/reports/activities/activity_date'), Assets::Image(Template::theme_url('images/calendar.png'), array(), true)); ?>
		<p><strong><?php echo lang('activity_date'); ?></strong><br/>
            <span><?php echo lang('activity_date_description'); ?></span>
		</p>
	</div>
	<?php endif; ?>
</div>
<div class="row">
	<div class="column size1of2">
		<!-- Active Modules -->
		<div class="admin-box">
			<h3><?php echo lang('activity_top_modules'); ?></h3>
			<?php if (isset($top_modules) && is_array($top_modules) && count($top_modules)) : ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th><?php echo lang('activity_module'); ?></th>
                        <th><?php echo lang('activity_logged'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_modules as $top_module) : ?>
                    <tr>
                        <td><strong><?php echo ucwords($top_module->module); ?></strong></td>
                        <td><?php echo $top_module->activity_count; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
			<?php
            else :
                echo lang('activity_no_top_modules');
            endif;
            ?>
		</div>
	</div>
	<div class="column size1of2 last-column">
		<div class="admin-box">
			<!-- Active Users -->
			<h3><?php echo lang('activity_top_users'); ?></h3>
			<?php if (isset($top_users) && is_array($top_users) && count($top_users)) : ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th><?php echo lang('activity_user'); ?></th>
                        <th><?php echo lang('activity_logged'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_users as $top_user) : ?>
                    <tr>
                        <td><strong><?php e($top_user->username == '' ? lang('activity_user_not_found') : $top_user->username); ?></strong></td>
                        <td><?php echo $top_user->activity_count; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
			<?php
            else :
                echo lang('activity_no_top_users');
            endif;
            ?>
		</div>
	</div>
</div>
<div class="admin-box">
	<h3><?php echo lang('activity_cleanup'); ?></h3>
	<table class="table table-striped activity-cleanup">
		<tbody>
			<?php if (has_permission('Activities.Own.Delete')) : ?>
			<tr>
                <td><label for="activity_own_select"><?php echo lang('activity_delete_own_note'); ?></label></td>
				<td>
					<?php echo form_open(SITE_AREA . '/reports/activities/delete', array('id' => 'activity_own_form', 'class' => 'form-inline')); ?>
                        <input type="hidden" name="action" value="activity_own" />
                        <select name="which" id="activity_own_select">
                            <option value="<?php echo $current_user->id; ?>"><?php e($current_user->username); ?></option>
                        </select>
					<?php echo form_close(); ?>
				</td>
				<td><button type="button" class="btn btn-danger btn-block" id="delete-activity_own"><?php echo $delete_icon . lang('activity_own_delete'); ?></button></td>
			</tr>
			<?php
                $empty_table = false;
            endif;
            if (has_permission('Activities.User.Delete')) :
            ?>
			<tr>
                <td><label for="activity_user_select"><?php echo lang('activity_delete_user_note'); ?></label></td>
				<td>
					<?php echo form_open(SITE_AREA . '/reports/activities/delete', array('id' => 'activity_user_form', 'class' => 'form-inline')); ?>
                        <input type="hidden" name="action" value="activity_user" />
                        <select name="which" id="activity_user_select">
                            <option value="all"><?php echo lang('activity_all_users'); ?></option>
                            <?php foreach ($users as $au) : ?>
                            <option value="<?php echo $au->id; ?>"><?php e($au->username); ?></option>
                            <?php endforeach; ?>
                        </select>
					<?php echo form_close(); ?>
				</td>
				<td><button type="button" class="btn btn-danger btn-block" id="delete-activity_user"><?php echo $delete_icon . lang('activity_user_delete'); ?></button></td>
			</tr>
			<?php
                $empty_table = false;
            endif;
            if (has_permission('Activities.Module.Delete')) :
            ?>
			<tr>
                <td><label for="activity_module_select"><?php echo lang('activity_delete_module_note'); ?></label></td>
				<td>
					<?php echo form_open(SITE_AREA . '/reports/activities/delete', array('id' => 'activity_module_form', 'class' => 'form-inline')); ?>
                        <input type="hidden" name="action" value="activity_module" />
                        <select name="which" id="activity_module_select">
                            <option value="all"><?php echo lang('activity_all_modules'); ?></option>
                            <option value="core"><?php echo lang('activity_core'); ?></option>
                            <?php foreach ($modules as $mod) : ?>
                            <option value="<?php echo $mod; ?>"><?php echo $mod; ?></option>
                            <?php endforeach; ?>
                        </select>
					<?php echo form_close(); ?>
				</td>
				<td><button type="button" class="btn btn-danger btn-block" id="delete-activity_module"><?php echo $delete_icon . lang('activity_module_delete'); ?></button></td>
			</tr>
			<?php
                $empty_table = false;
            endif;
            if (has_permission('Activities.Date.Delete')) :
            ?>
			<tr>
                <td><label for="activity_date_select"><?php echo lang('activity_delete_date_note'); ?></label></td>
				<td>
                    <?php echo form_open(SITE_AREA . '/reports/activities/delete', array('id' => 'activity_date_form', 'class' => 'form-inline')); ?>
                        <input type="hidden" name="action" value="activity_date" />
                        <select name="which" id="activity_date_select">
                            <option value="all"><?php echo lang('activity_all_dates'); ?></option>
                            <?php foreach ($activities as $activity) : ?>
                            <option value="<?php echo $activity->activity_id; ?>"><?php echo $activity->created_on; ?></option>
                            <?php endforeach; ?>
                        </select>
					<?php echo form_close(); ?>
				</td>
				<td><button type="button" class="btn btn-danger btn-block" id="delete-activity_date"><?php echo $delete_icon . lang('activity_date_delete'); ?></button></td>
			</tr>
			<?php
                $empty_table = false;
            endif;
            if ($empty_table) :
            ?>
			<tr>
				<td><?php echo lang('activity_none_found'); ?></td>
			</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>