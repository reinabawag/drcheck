<div class="panel panel-primary">
	<div class="panel-heading">REPORT</div>
	<div class="panel-body">
		<form id="frm-rpt" method="POST">
			<div class="form-group">
				<label for="">Start Date</label>
				<input type="text" name="startDate" class="form-control" value="<?php echo date('m/d/Y', now()) ?>" placeholder="Date" maxlength="0" id="startDate">
			</div>
			<div class="form-group">
				<label for="">End Date</label>
				<input type="text" name="endDate" class="form-control" value="<?php echo date('m/d/Y', now()) ?>" placeholder="Date" maxlength="0" id="endDate">
			</div>
			<div class="form-group">
				<label for="">Type</label>
				<select name="type" id="type" class="form-control">
					<option value="1">DR LOGIN/LOGOUT</option>
					<option value="2">DR LOGS</option>
				</select>
			</div>
			<button class="btn btn-success btn-sm">Generate</button>
		</form>
	</div>
</div>
<div class="panel panel-primary">
	<div class="panel-heading">DR SUMMARY</div>
	<div class="panel-body">
		<form id="dr-summary" method="POST">
			<div class="form-group">
				<label for="">DR DATE</label>
				<input type="text" name="dr-date" class="form-control" value="" placeholder="Please select dr date" maxlength="0" id="dr-date">
			</div>
			<div class="checkbox">
				<label>
					<input type="checkbox" id="chkbox" checked="">Show All
				</label>
			</div>
			<div class="form-group" style="display: none;">
				<label for="">DR NUMBER</label>
				<select name="drNumber" id="drNumber" class="form-control"></select>
			</div>
			<button class="btn btn-success btn-sm">Generate</button>
		</form>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function() {
		$('#startDate').datepicker({
			autoclose: true,
			todayBtn: true,
			todayHighlight: true
		});

		$('#endDate').datepicker({
			autoclose: true,
			todayBtn: true,
			todayHighlight: true
		});

		$('#dr-date').datepicker({
			autoclose: true,
			todayBtn: true,
			todayHighlight: true
		});

		$('#frm-rpt').ajaxForm({
			url: '<?php echo site_url('admin/generate_report') ?>',
			beforeSubmit: function() {
				if ($('#startDate').val() > $('#endDate').val()) {
					toastr.info("Start date cannot be greater than End date");
					return false;
				}
			},
			error: function() {
				console.log('error');
				toastr.error('Error in Controller: admin/generate_report');
			}
		})

		$('#frm-rpt').submit(function(e) {
			e.preventDefault();
			var type = $('#type').val();
			var startDate = $('#startDate').val();
			var endDate = $('#endDate').val();
			
			if (type == '1') {
				window.open('<?php echo site_url('admin/login_report_new') ?>/?startDate='+encodeURIComponent(startDate)+'&endDate='+encodeURIComponent(endDate));
				return false;
			} else if (type == '2') {
				// window.open('<?php echo site_url('admin/dr_report') ?>/?startDate='+encodeURIComponent(startDate)+'&endDate='+encodeURIComponent(endDate));
				window.open('<?php echo site_url('admin/dr_logs_report') ?>/?startDate='+encodeURIComponent(startDate)+'&endDate='+encodeURIComponent(endDate));
				// window.open('<?php echo site_url('admin/new_report') ?>/?startDate='+startDate+'&endDate='+endDate);
				return false;	
			}
		})

		$('#dr-date').change(function(e) {
			$('#drNumber').empty();
			$.get('<?php echo site_url('admin/get_dr_number_by_date') ?>', {drDate: $(this).val()}, function(data) {
				$.each(data, function(index, elem) {
					$('#drNumber').append('<option>'+elem.number+'</option>');
				});
			}, 'json')
		});

		$('#chkbox').change(function() {
			if ($('#chkbox').is(":checked")) { $('#drNumber').parent('div').hide() } else { $('#drNumber').parent('div').show() }
		});

		$('#dr-summary').submit(function(e) {
			e.preventDefault();

			var date = $('#dr-date').val();
			var number = $('#drNumber').val();

			if (number == null) {
				toastr.info('No DR Filed for the selected date.<br>Please choose another date');
				return false;
			} else {
				if ($('#chkbox').is(":checked")) {
					window.open('<?php echo site_url('admin/dr_summary_new') ?>/?date='+encodeURIComponent(date), 'DR SUMMARY');
				} else {
					window.open('<?php echo site_url('admin/dr_summary') ?>/?date='+ encodeURIComponent(date) +'&drNumber='+number);
					return false;
				}
			}
		})
	})
</script>