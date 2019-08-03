<div class="row">
	<div class="col-md-6 col-sm-6">
		<div class="panel panel-success">
		  	<div class="panel-heading">ITEM RETURN <strong><?php echo $this->session->is_supervisor ? 'SUPERVISOR' : 'CHECKER'?></strong></div>
		  	<div class="panel-body">
		  		<?php echo form_open() ?>
		    	<div class="form-group">
		    		<label for="drDate" class="control-label">DR DATE</label>
		    		<input type="text" name="date" id="date" class="form-control" value="" placeholder="PLEASE SELECT DATE">
		    	</div>
		    	<div class="form-group">
		    		<label for="number" class="control-label">DR NUMBER</label>
		    		<select name="number" class="form-control" id="number" placeholder="DR NUMBER">
		    		</select>
		    	</div>
		    	<div class="form-group">
		    		<label for="barcode" class="control-label">ITEM CODE</label>
		    		<input type="text" name="item" class="form-control" id="barcode" placeholder="ITEM CODE">
		    		<input type="hidden" name="co_line" id="co_line">
		    		<p id="count" style="display: none;"></p>
		    	</div>
		    	<div class="form-group">
		    		<label for="lot" class="control-label">LOT NO.</label>
		    		<input type="text" name="lot" class="form-control" id="lot" placeholder="LOT NO.">
		    	</div>
		    	<div class="form-group">
		    		<label for="qty" class="control-label">QTY</label>
		    		<input type="text" name="qty" class="form-control" id="qty" placeholder="QTY">
		    	</div>
		    	<button class="btn btn-warning" type="reset" id="reset">RESET</button>
		    	<?php echo form_close()	 ?>
		  	</div>
		</div>
	</div>

	<div class="col-md-6 col-sm-6">
		<div class="panel panel-success">
		  	<div class="panel-heading">ITEM LIST</div>
		  	<div class="panel-body">
		  		<div class="table-responsive">
		  			<table class="table table-striped" id="tbl-list" >
		  				<thead>
		  					<tr>
		  						<th>ITEM DESCRIPTION</th>
		  						<th>LOT NO.</th>
		  						<th>QTY</th>
		  						<th>DELIVERED</th>
		  						<th>RETURNED</th>
		  					</tr>
		  				</thead>
		  				<tbody></tbody>
		  			</table>
					<input type="hidden" name="list-count">
		  		</div>
		  	</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function() {
		$('#date').datepicker({
			autoclose: true
		});

		$('#date').change(function(e) {
			e.preventDefault();

			$.post('<?php echo site_url('main/getDRNumberByDate') ?>', {date: $(this).val()}, null, 'json')
			.then(function(data) {
				if (data.length == 0) {
					toastr.info('No DR filed for the selected date', 'Info');
				}
				$('#number').empty();
				$.each(data, function(index, elem) {
					$('#number').append('<option>'+elem.number+'</option>');
				});

				return $.post('<?php echo site_url('main/getDrList') ?>', {date: $('#date').val(), number: $('#number').val()}, null, 'json');
			})
			.done(function(data) {
				$('#barcode').focus();
				$('#tbl-list tbody').empty();
				$.each(data, function(index, elem) {
					$('#tbl-list tbody').append('<tr><td>'+elem.description+'</td><td>'+elem.lotNo+'</td><td>'+elem.qty+'</td><td>'+elem.ctr+'</td><td><strong>'+elem.returned+'</strong></td></tr>');
				})
			})
			.fail(function() {
				toastr.error('Error in loading dr number by date', 'Error');
			})
		});

		$('#number').change(function(e) {
			e.preventDefault();

			loadTable();
			$('#barcode').focus();
		});

		$('#barcode').keyup(function(e) {
			e.preventDefault();
			var barcode = $(this);
			if (e.keyCode == 13) {
				barcode.parent('div').removeClass('has-error').removeClass('has-success');

				if ($('#tbl-list tbody tr').length == 0) {
					$(this).val('');
					toastr.info('No item to be returned', 'Info');
				} else {
					$.post('<?php echo site_url('main/process_return_item') ?>', {date: $('#date').val(), number: $('#number').val(), item: $('#barcode').val()}, null, 'json')
					.then(function(data) {
						if (data.status) {

							if (parseInt(data.info['lot_tracked']) == 0) {
								toastr.success('Item returned', 'Success');
								barcode.parent('div').addClass('has-success');
							} else {
								toastr.success('Item Code matched the DR LIST', 'Item Code');
								barcode.parent('div').addClass('has-success');
							}	

							if (parseInt(data.info['lot_tracked']) == 0) {barcode.val('');
							} else {
								$('#lot').focus();
								// Additional 6/6/17
								$('#co_line').val(data.info['co_line']);
							}
						} else {
							toastr.warning(data.msg, 'Invalid Item Code Scanned');
							barcode.parent('div').addClass('has-error');
							barcode.val('');
						}

						loadTable();
					})
					.fail(function() {
						toastr.error('Error in returning item', 'Error');
					})
				}
			}
		});

		$('#lot').keyup(function(e) {
			e.preventDefault();
			var lot = $(this);

			if (e.keyCode == 13) {
				if (lot.val() == '') {
					toastr.info('Lot number is empty', 'Empty Lot Number');
				} else {
					lot.parent('div').removeClass('has-success').removeClass('has-error');
					$.post('<?php echo site_url('main/checkLotNo') ?>', {date: $('#date').val(), number: $('#number').val(), item: $('#barcode').val(), lot: lot.val(), co_line: $('#co_line').val()}, null, 'json')
					.then(function(data) {
						if (data.status) {
							toastr.success(data.msg, 'Success');
							lot.parent('div').addClass('has-success');
							$('#qty').focus();
						} else {
							toastr.warning(data.msg, 'Invalid Lot No Scanned');
							lot.parent('div').addClass('has-error');
							lot.val('');
						}

						loadTable();
					})	
					.fail(function() {
						toastr.error('Error in checking lot no', 'Error');
					})
				}
			}
		});

		$('#qty').keyup(function(e) {
			e.preventDefault();
			var qty = $(this);

			if (e.keyCode == 13) {
				if (qty.val() == '') {
					toastr.info('Quantity is empty', 'Please scan quantity');
				} else {
					qty.parent('div').removeClass('has-success').removeClass('has-error');
					$.post('<?php echo site_url('main/checkQty') ?>', {date: $('#date').val(), number: $('#number').val(), item: $('#barcode').val(), lot: $('#lot').val(), qty: qty.val(), co_line: $('#co_line').val()}, null, 'json')
					.then(function(data) {
						if (data.status) {
							toastr.success(data.msg, 'Success');
							qty.parent('div').addClass('has-success');
							$('#barcode').val('').focus();
							$('#lot').val('');
							qty.val('');
							$('form :input').parent('div').removeClass('has-success');
							loadTable();
						} else {
							toastr.warning(data.msg, 'Invalid Quantity Scanned');
							qty.parent('div').addClass('has-error');
							qty.val('');
							$(':input').parent('div').removeClass('has-success');
						}
					})	
					.fail(function() {
						toastr.error('Error in checking quantity', 'Error');
					})
				}
			}
		});

		$('#reset').click(function(e) {
			$('#number').empty();
		})

		function loadTable(date, number) {
			$.post('<?php echo site_url('main/getDrList') ?>', {date: $('#date').val(), number: $('#number').val()}, null, 'json')
			.then(function(data) {
				$('#tbl-list tbody').append('<tr><td colspan="4">Please wait...</td></tr>');
				$('#tbl-list tbody').empty();
				$.each(data, function(index, elem) {
					// showing DR LIST for item transacted normally
					// $('#tbl-list tbody').append('<tr><td>'+elem.description+'</td><td>'+elem.lotNo+'</td><td>'+elem.qty+'</td><td>'+elem.p_ctr+'</td><td><strong>'+elem.returned+'</strong></td></tr>');
					$('#tbl-list tbody').append('<tr><td>'+elem.description+'</td><td>'+elem.lotNo+'</td><td>'+elem.qty+'</td><td>'+elem.ctr+'</td><td><strong>'+elem.returned+'</strong></td></tr>');
				});
			})
			.fail(function() {
				toastr.error('Error in loading loadTable function', 'Error');
			})
		};
	})
</script>