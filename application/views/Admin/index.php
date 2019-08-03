<div class="panel panel-primary">
	<div class="panel-heading">DR OPTIONS</div>
	<div class="panel-body">
		<form class="form-inline" method="" id="dr-search" method="get">
			<div class="form-group">
				<label for="search">Search:</label>
				<?php echo form_input('search', '', ['class' => 'form-control', 'placeholder' => 'Keyword', 'id' => 'search']) ?>
			</div>
			<input type="submit" class="btn btn-default" value="Search">
			<!-- <input type="reset" name="reset" class="btn btn-warning"> -->
			<button class="btn btn-info" id="btn-modal-upload">UPLOAD</button>
			<button class="btn btn-primary" id="btn-import-syteline">IMPORT FROM SYTELINE</button>
		</form>
	</div>
</div>

<div class="panel panel-primary">
	<div class="panel-heading">DR TABLE</div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-condensed table-striped table-hover" id="dr-table" style="font-size: 12px;">
				<thead>
					<th>DR DATE</th>
					<th>DR NUMBER</th>
					<th>CUSTOMER</th>
					<th>CO</th>
					<th>LINE</th>
					<th>ITEM CODE</th>
					<th>DESCRIPTION</th>
					<th>LOT NO</th>
					<th>QTY</th>
					<th>UM</th>
					<th>TRACKED</th>
				</thead>
				<tbody></tbody>
			</table>
		</div>
		<div id="pagination"></div>
	</div>
</div>

<div class="modal fade" id="modal-upload" data-backdrop="static">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="myModalLabel">Import Excel to Database</h4>
			</div>
			<div class="modal-body">
				<table class="table table-bordered" id="tbl-file-list">
					<thead>
						<th>Filename</th>
						<th>Date</th>
						<th>Option</th>
					</thead>
					<tbody></tbody>
				</table>
				<p id="import-message"></p>
				<?php echo form_open_multipart('admin/file_upload', ['id' => 'form-upload']); ?>
				<div class="form-group" style="margin-top: 10px">
					<label for="upload">SELECT FILE</label>
					<input type="file" name="userfile" id="upload">
				</div>
				<button class="btn btn-primary" id="btn-upload">UPLOAD</button>
				<?php echo form_close(); ?>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
				<button id="btn-get-files" class="btn btn-success">GET FILE LIST</button>
				<!-- <button type="button" class="btn btn-primary" id="btn-upload">Auto-Upload</button> -->
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="systeline-import" data-backdrop="static">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Import From Syteline</h4>
			</div>
			<div class="modal-body">
				<?php echo form_open('syteline/import', ['id' => 'syteline-form']) ?>
					<div class="form-group">
						<label for="syteline-date" class="control-label">Date</label>
						<input type="text" name="syteline-date" id="syteline-date" class="form-control" maxlength="0" placeholder="Please select date to import">
					</div>
					<div class="form-group" id="loading" style="display: none;">
						<span id="syteline-msg"></span>&nbsp;<?php echo img('assets/img/hourglass.gif', FALSE, array('width' => '24px')) ?>
					</div>
					<button class="btn btn-success">IMPORT</button>
				<?php echo form_close() ?>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function() {

		loadTable('<?php echo site_url('admin/get_dr_list') ?>');

		$('#form-upload').submit(function(e) {
			e.preventDefault();

			$.ajax({
				url: $('form#form-upload').prop('action'),
				type: 'POST',
				processData: false,
    			contentType: false,
			  	data: new FormData(this),
			  	dataType: 'json'
			})
			.done(function(data) {
				if (data.status) {
					toastr.success('File Uploaded Succssfully');
					$('#btn-get-files').trigger('click');
				} else {
					toastr.info(data.message);
				}
			})
			.fail(function() {
				toastr.error('Problem Uploading File');
			})
		})

		$('#btn-get-files').on('click', function(e) {
			$('table#tbl-file-list').fadeOut();
			$('table#tbl-file-list tbody').empty();
			$.ajax({
				url: '<?php echo site_url('admin/read_directory') ?>',
				type: 'GET',
				dataType: 'json'
			})
			.then(function(data) {
				$('table#tbl-file-list').fadeIn();
				$.each(data, function(index, elem) {
					$('table#tbl-file-list tbody').append('<tr><input type="hidden" name="fname" value="'+elem.filename+'"><td>'+elem.filename+'</td><td>'+elem.date+'</td><td><a href="#" temp="'+elem.filename+'">Import</a></td></tr>');
				})
			}).then(function() {
				$('table#tbl-file-list tr a').click(function(e) {
					e.preventDefault();

					var filename = $(this).closest('tr').find('a').attr('temp');
					$('#import-message').html('<?php echo img('assets/img/hourglass.gif', FALSE, ['width' => 24]) ?> Importing file please wait...');
					return $.post('<?php echo site_url('admin/parse_excel') ?>', {filename: filename}, function(data) {
						if (data.status) {toastr.success(data.message); loadTable('');} else {toastr.error(data.message)};
					},'json')
					.fail(function() {
						toastr.info('Invalid File Format Or Structure<br>Please Try Again Later');
					})
					.always(function() {
						$('#import-message').empty();
					})
				})
			}).fail(function() {
				toastr.error('Problem In Controller: admin/read_directory');
			})
		})

		$('#btn-modal').click(function(e) {
			e.preventDefault();

			$('#myModal').modal('show');
		});

		$('#datePicker').datepicker({
			todayBtn: "linked",
			autoclose: true,
			todayHighlight: true,
			toggleActive: true
		});

		$('#myModal').on('hidden.bs.modal', function() {
			$('.modal form :input[type="text"]').val('');
		});

		$('#btn-modal-upload').click(function(e) {
			e.preventDefault();

			$('#modal-upload').modal('show');
		});

		$('#btn-create').click(function() {
			$.ajax({
				url: '<?php echo site_url('admin/create_dr') ?>',
				type: 'POST',
				dataType: 'json',
				data: $('form#dr-form').serialize()
			}).then(function(data) {
				if (data.status) {
					toastr.success(data.message);
					$('#myModal').modal('hide');
				} else {
					toastr.info(data.message);
				}
				return loadTable();
			}).fail(function() {
				toastr.error("Cannot Process Request.<br>Please contact MIS");
			})
		});

		$('#dr-search').submit(function(e) {
			e.preventDefault();
			searchTable('');			
		});

		$('#btn-import-syteline').click(function(e) {
			e.preventDefault();

			$('#systeline-import').modal('show');
		});

		$('#systeline-import').on('hide.bs.modal', function(e) {
			console.log('work');
			$('#syteline-date').parent('div').removeClass('has-warning', 'has-success');
		})

		$('#syteline-form').ajaxForm({
			dataType: 'json',
			beforeSubmit: function() {
				$('#loading').show();
				$('span#syteline-msg').html('Importing please wait...');
				$('input[name="syteline-date"]').parent('div').removeClass('has-warning').removeClass('has-success');

				if ($('input[name="syteline-date"]').val() == '') {
					toastr.warning('Please select date first', 'Date');
					$('input[name="syteline-date"]').parent('div').addClass('has-warning');
					$('span#syteline-msg').empty();
					$('#loading').hide();
					return false
				}
			},
			success: function(data) {
				$('#loading').hide();
				$('input[name="syteline-date"]').datepicker('update', '');
				if (data.status == false) {
					$('input[name="syteline-date"]').parent('div').addClass('has-warning').focus();
					toastr.info(data.message, 'Date');
					$('span#syteline-msg').empty();
				} else {
					$('input[name="syteline-date"]').parent('div').removeClass('has-warning').addClass('has-success');
					toastr.success('Succssfully imported data from Syteline', 'Import');
					$('span#syteline-msg').empty();
					$('#systeline-import').modal('hide');
					loadTable('');
				}
			},
			error: function() {
				toastr.error('Error in importing data from Systeline', 'Import Error');
				$('span#syteline-msg').html('');
				$('#loading').hide();
			}
		});

		$('#systeline-import').on('hidden.bs.modal', function() {
			$('input[name="syteline-date"]').parent('div').removeClass('has-warning').removeClass('has-success');
			$('input[name="syteline-date"]').datepicker('update', '');
		})

		$('input[name="syteline-date"]').datepicker({
			todayBtn: "linked",
			autoclose: true,
			todayHighlight: true,
			toggleActive: true
		});

		function searchTable(url) {
			if (url == '') {
				url = '<?php echo site_url('admin/search') ?>';
			}
			
			$.ajax({
				type: 'GET',
				url: url,
				data: $('#dr-search').serialize(),
				dataType: 'json'
			}).then(function(data) {
				$('tbody').empty();
				$.each(data[0]['results'], function(index, elem) {
					$('#dr-table tbody').append('<tr><td>'+elem.date+'</td><td>'+elem.number+'</td><td>'+elem.customer+'</td><td>'+elem.co+'</td><td>'+elem.co_line+'</td><td>'+elem.itemCode+'</td><td>'+elem.description+'</td><td>'+elem.lotNo+'</td><td>'+elem.qty+'</td><td>'+elem.um+'</td><td>'+elem.lot_tracked+'</td></tr>')
				});
				$('#pagination').html(data.pagination);
			}).then(function() {
				$('.pagination li a').click(function(e) {
					e.preventDefault();
					if ($(this).attr('href') != '#') {
						searchTable($(this).attr('href'));
					}					
				})
			}).fail(function() {
				toastr.error('Cannot Search DR List.<br>Please Contact MIS');
			});
		}

		function loadTable(page) {
			$('#dr-table tbody').append('<tr><td colspan="5">Loading Please Wait...</td></tr>');
			var url;
			if (page == '') {
				url = '<?php echo site_url('admin/get_dr_list') ?>';
			} else {
				url = page;
			}

			$.ajax({
				type: 'GET',
				url: url,
				dataType: 'json'
			}).then(function(data) {
				$('#dr-table tbody').empty();
				$.each(data['1'], function(index, elem) {
					$('#dr-table tbody').append('<tr><td>'+elem.date+'</td><td>'+elem.number+'</td><td>'+elem.customer+'</td><td>'+elem.co+'</td><td>'+elem.co_line+'</td><td>'+elem.itemCode+'</td><td>'+elem.description+'</td><td>'+elem.lotNo+'</td><td>'+elem.qty+'</td><td>'+elem.um+'</td><td>'+elem.lot_tracked+'</td></tr>')
				});
				$('#pagination').html(data.pagination);
			}).done(function() {
				$('.pagination li a').click(function(e) {
					e.preventDefault();
					if ($(this).attr('href') != '#') {
						loadTable($(this).attr('href'));
					}
				})
			}).fail(function() {
				toastr.error('Cannot Load DR List.<br>Please Contact MIS');
			})
		}
	})
</script>