<div class="row">
	<div class="col-md-6 col-sm-6">
		<div class="panel panel-primary">
		  	<div class="panel-heading">DR OPTION <strong><?php echo $this->session->is_supervisor ? 'SUPERVISOR' : 'CHECKER'?></strong></div>
		  	<div class="panel-body">
		  		<?php echo form_open() ?>
		    	<div class="form-group">
		    		<label for="drDate" class="control-label">DR DATE</label>
		    		<input type="text" name="" id="drDate" class="form-control" maxlength="0" placeholder="PLEASE SELECT DATE">
		    	</div>
		    	<div class="form-group">
		    		<label for="drNumber" class="control-label">DR NUMBER</label>
		    		<select name="drNumber" class="form-control" id="drNumber" placeholder="DR NUMBER">
		    		</select>
		    	</div>
		    	<div class="form-group">
		    		<label for="barcode" class="control-label">ITEM CODE</label>
		    		<input type="text" name="" class="form-control" id="barcode" placeholder="ITEM CODE">
		    		<p id="count" style="display: none;"></p>
		    		<input type="hidden" name="box" id="box" value="" ctr=0>
		    		<input type="hidden" name="noLot" id="noLot">
		    		<input type="hidden" name="tmpItemCode" id="tmpItemCode">
		    		<input type="hidden" name="co_line" id="co_line">
		    	</div>
		    	<div class="form-group" align="">
		    		<label class="checkbox">
		    		<!-- <?php echo $this->session->is_supervisor ? 'checked' : null ?> -->
		    			<input type="checkbox"  data-toggle="toggle" id="toggle-type" data-on="RECHECK" data-off="NORMAL" data-onstyle="success" data-offstyle="primary" name="toggle-status"> <!-- <span id="span-toggle">Normal</span> -->
		    		</label>
		    	</div>
		    	<div class="form-group">
		    		<label for="lotNo" class="control-label">LOT NO.</label>
		    		<input type="text" name="" class="form-control" id="lotNo" placeholder="LOT NO.">
		    	</div>
		    	<div class="form-group">
		    		<label for="qty" class="control-label">QTY</label>
		    		<input type="text" name="" class="form-control" id="qty" placeholder="QTY">
		    	</div>
		    	<button class="btn btn-warning" type="reset" id="reset">RESET</button>
		    	<?php echo form_close()	 ?>
		  	</div>
		</div>
	</div>

	<div class="col-md-6 col-sm-6">
		<div class="panel panel-primary">
		  	<div class="panel-heading">DR LIST</div>
		  	<div class="panel-body">
		  		<span id="cust-name"></span>
		  		<div class="table-responsive">
		  			<table class="table table-striped" id="drList" ><!-- style="font-size: 10px" -->
		  				<thead>
		  					<th>ITEM DESCRIPTION</th>
		  					<th>LOT NO.</th>
		  					<th>QUANTITY</th>
		  					<th>STATUS</th>
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
	$(document).ready(function(){
		$('#drDate').datepicker({
			autoclose: true,
		});

		$('#toggle-type').change(function() {
			$('#count').hide();
			$('#box').attr('val', 0);
			$('#box').attr('ctr', 0);
			resetInput();

			$.post('<?php echo site_url('admin/show_dr_by_date') ?>', {date: $('#drDate').val(), number: $('#drNumber').val(), toggle: $('#toggle-type').prop('checked')}, function(data) {
				$('#drList tbody').empty();
				var c_style;
				$.each(data, function(index, elem) {
					$('#drList tbody').append('<tr class="'+c_style+'"><td>'+elem.description+'</td><td>'+elem.lotNo+'</td><td>'+elem.qty+'</td><td>'+elem.img+'</td></tr>');
				});
			}, 'json');
		})

		$('#drNumber').change(function() {
			$.post('<?php echo site_url('admin/show_dr_by_date') ?>', {date: $('#drDate').val(), number: $('#drNumber').val(), toggle: $('#toggle-type').prop('checked')}, function(data) {
				$('#drList tbody').empty();
				$('p#count').hide();
				$('#box').attr('ctr', 0).attr('val', 0);
				$.each(data, function(index, elem) {
					$('#cust-name').html('<strong>'+elem.customer+'</strong>');
					$('#drList tbody').append('<tr><td>'+elem.description+'</td><td>'+elem.lotNo+'</td><td>'+elem.qty+'</td><td>'+elem.img+'</td></tr>');
				});
				$('input[name="list-count"]').val(countProperties(data));
			}, 'json')
			.done(function() {
				if ($("table img").length == parseInt($('input[name="list-count"]').val())) {
					// toastr.success('All items checked', 'Success');
					var number = $('#drNumber').val();
					$('#drNumber [value="'+number+'"]').html(number+' checked');
				}
				$('#barcode').focus();
			})
			.fail(function() {
				toastr.error('Error in Loading DR LIST');
			})
		})

		$('#reset').click(function() {
			$('#drNumber').empty();
		})
		
		function countProperties(obj) {
			var count = 0;
			for (var property in obj) {
				if (Object.prototype.hasOwnProperty.call(obj, property)) {
					count++;
				}
			}
			return count;
		}

		function resetInput() {
			$('form :input').parent('div').removeClass('has-success').removeClass('has-error');
			$('#barcode').val('').focus();
			$('#lotNo').val('');
			$('#qty').val('');
		}

		$('#drDate').change(function(e) {
			$('#barcode').val('');
			$('#lotNo').val('');
			$('#qty').val('');

			$('form :input').parent('div').removeClass('has-error').removeClass('has-success');

			$.ajax({
				url: '<?php echo site_url('admin/get_dr_number_by_date') ?>',
				type: 'GET',
				data: {drDate: $('#drDate').val()},
				dataType: 'json'
			}).then(function(data) {
				var ctr = 0;
				$('#drNumber').empty();
				$('#barcode').focus();
				$.each(data, function(index, elem) {
					ctr = parseInt(ctr) + 1;
					$('#drNumber').append('<option value="'+elem.number+'">'+elem.number+'</option>');
				});

				if (ctr == 0) {toastr.info('No DR Filed For This Date');}
				
				return $.post('<?php echo site_url('admin/show_dr_by_date') ?>', {date: $('#drDate').val(), number: $('#drNumber').val(), toggle: $('#toggle-type').prop('checked')}, function() {}, 'json');
				
			}).done(function(data) {
				$('#drList tbody').empty();
				$.each(data, function(index, elem) {
					$('#cust-name').html('<strong>'+elem.customer+'</strong>');
					$('#drList tbody').append('<tr><td>'+elem.description+'</td><td>'+elem.lotNo+'</td><td>'+elem.qty+'</td><td>'+elem.img+'</td></tr>');
				})				
				$('input[name="list-count"]').val(countProperties(data));
			}).done(function() {
				if ($("table img").length == parseInt($('input[name="list-count"]').val()) && $("table img").length > 0) {
					toastr.success('All items checked', 'Success');
				}
			}).fail(function() {
				toastr.error('Error in Controller: admin/show_dr_by_date');
			}).always(function() {
				$('#box').attr('val', 0);
				$('#box').attr('ctr', 0);
			})
		});

		$('#reset').click(function() {
			$('#drDate').attr('disabled', false);
		});

		$('#barcode').keyup(function(e) {
			var bcode = $('#barcode').val();
			$('#box').val(bcode);

			if (e.keyCode == 13) {
				$.ajax({
					url: '<?php echo site_url('admin/checkBCode') ?>',
					type: 'POST',
					dataType: 'json',
					data: {bCode: $('#barcode').val(), date: $('#drDate').val(), number: $('#drNumber').val(), recheck: $('#toggle-type').prop('checked')}
				}).then(function(data) {
					if (data == null) {
						toastr.error('Barcode Doesn\'t Match With DR NUMBER');
						$('#barcode').parent('div').addClass('has-error');
						$('#barcode').val('');
					}else {
						$('#barcode').parent('div').removeClass('has-error').addClass('has-success');

						// for validation if same item diff item code but w/o lot no
						if ($('#box').val() != data.itemCode) {
							if ($('#toggle-type').prop('checked')) {
								$.post('<?php echo site_url('admin/set_p_ctr_by_item') ?>', {date: $('#drDate').val(), number: $('#drNumber').val(), item: $('#box').val(), ctr: $('#box').attr('ctr')}, 'json');
								$('#box').attr('val', 0);
								$('#box').attr('ctr', 0);
							} else {
								$.post('<?php echo site_url('admin/set_ctr_by_item') ?>', {date: $('#drDate').val(), number: $('#drNumber').val(), item: $('#box').val(), ctr: $('#box').attr('ctr')}, 'json');
								$('#box').attr('val', 0);
								$('#box').attr('ctr', 0);
							}
						}

						if (data.lot_tracked == 1) {

							// Additional 6/6/17
							// $('#co_line').val(data.co_line);

							if ($('#toggle-type').prop('checked')) {
								$.post('<?php echo site_url('admin/set_p_ctr_by_item') ?>', {date: $('#drDate').val(), number: $('#drNumber').val(), item: $('#box').val(), ctr: $('#box').attr('ctr')}, 'json');
								$('#box').attr('val', 0);
								$('#box').attr('ctr', 0);
							} else {
								$.post('<?php echo site_url('admin/set_ctr_by_item') ?>', {date: $('#drDate').val(), number: $('#drNumber').val(), item: $('#box').val(), ctr: $('#box').attr('ctr')}, 'json');
								$('#box').attr('val', 0);
								$('#box').attr('ctr', 0);
							}
							
							$('#count').hide().empty();
							$('#lotNo').focus();
						} else  {
							$('#noLot').val(data.status);
							$('#co_line').val(data.co_line);
							if (false) {
								$('#co_line').val(data.co_line);

								// Improvements for rechecking
								if ($('#toggle-type').prop('checked') == false) {
									toastr.info('Item Already Scanned', 'Item');
									$('#barcode').val('');
								} else {
									$('#box').attr('val', parseInt(data.qty));
									var temp = $('#box').attr('ctr');

									temp = $('#box').attr('ctr');
									temp = parseInt(temp) + 1;
									$('#box').attr('ctr', temp);
									$('#count').show().html('<em>Item Scanned: '+temp+'</em>');
									
									$('#box').val(bcode);
									$('#barcode').val('');

									if (temp == $('#box').attr('val')) {
										$('#box').attr('val', 0);
										$('#box').attr('ctr', 0);
										$('#barcode').val('');
										toastr.success('Item Cleared', 'Item');
										$('#count').hide();
										
										return $.post('<?php echo site_url('admin/change_item_status') ?>', {date: $('#drDate').val(), barcode: data.itemCode, lotNo: 'NULL', qty: data.qty, toggle: $('#toggle-type').prop('checked'), co_line: $('#co_line').val(), number: $('#drNumber').val(), number: $('#drNumber').val()}, 'json');
									}
								}
							} else {
								if (data.status == 1 && $('#toggle-type').prop('checked') == false) {
									toastr.info('Item Already Scanned', 'Item');
									$('#barcode').val('');
								} else {
									$('#box').attr('val', parseInt(data.qty));

									if ($('#toggle-type').prop('checked') == true && $('#box').attr('ctr') == 0) {
										$('#box').attr('ctr', parseInt(data.p_ctr));
									} else if ($('#toggle-type').prop('checked') == false && $('#box').attr('ctr') == 0) {
										$('#box').attr('ctr', parseInt(data.ctr));
									}

									var temp = $('#box').attr('ctr');
									if (temp != $('#box').attr('val')) {
										temp = parseInt(temp) + 1;
									} else {
										temp = parseInt(temp);
									}
									$('#box').attr('ctr', temp);
									$('#count').show().html('<em>Item Scanned: '+temp+'</em>');

									$.post('<?php echo site_url('admin/update_item') ?>', {date: $('#drDate').val(), number: $('#drNumber').val(), item: $('#barcode').val(), lot: 'NULL', qty: data.qty, toggle: $('#toggle-type').prop('checked'), ctr: parseInt($('#box').attr('ctr')), co_line: $('#co_line').val(), co_line: $('#co_line').val()}, null, 'json');
									
									$('#box').val(bcode);
									$('#barcode').val('');

									// added as temporary fix || temp > data.qty 09/21/2017
									if (temp == $('#box').attr('val') || temp > data.qty) {
										$('#box').attr('val', 0);
										$('#box').attr('ctr', 0);
										$('#barcode').val('');
										toastr.success('Item Cleared', 'Item');
										$('#count').hide();
										return $.post('<?php echo site_url('admin/change_item_status') ?>', {date: $('#drDate').val(), barcode: data.itemCode, lotNo: 'NULL', qty: data.qty, toggle: $('#toggle-type').prop('checked'), co_line: $('#co_line').val(), number: $('#drNumber').val(), number: $('#drNumber').val()}, 'json');
									}
								}
							}
						}
					}
				}).done(function(data) {
					return $.post('<?php echo site_url('admin/show_dr_by_date') ?>', {date: $('#drDate').val(), number: $('#drNumber').val(), toggle: $('#toggle-type').prop('checked')},
						function(data) {
							$('#drList tbody').empty();
							$.each(data, function(index, elem) {
								$('#drList tbody').append('<tr><td>'+elem.description+'</td><td>'+elem.lotNo+'</td><td>'+elem.qty+'</td><td>'+elem.img+'</td></tr>');
							})

							// for automatic generation of report disabled 04/07/2017
							// var date = $('#drDate').val();
							// var number = $('#drNumber').val();
							
							// if ($("table img").length == parseInt($('input[name="list-count"]').val())) {
							// 	window.open('<?php echo site_url('admin/dr_summary') ?>/?date='+date+'&drNumber='+number);
							// }
						}, 'json');
				}).fail(function(){
					toastr.error('Problem Checking Product Code', 'Error');
				})
			}
		})

		$('#lotNo').keyup(function(e) {
			var lotNo = $(this);
			if (e.keyCode == 13) {
				$.ajax({
					url: '<?php echo site_url('admin/checkLotNo') ?>',
					type: 'POST',
					data: {bCode: $('#barcode').val(), date: $('#drDate').val(), lotNo : lotNo.val(), number: $('#drNumber').val()},
					dataType: 'json'
				}).done(function(data) {
					if (data == '' || data == null) {
						$('#lotNo').parent('div').addClass('has-error');
						$('#lotNo').val('');
						toastr.error('Lot No. Doesn\' Match With DR Number');
					} else {
						$('#co_line').val(data.co_line);
						$('#lotNo').parent('div').removeClass('has-error').addClass('has-success');
						$('#qty').focus();
					}
				}).fail(function() {
					toastr.error('Error Encountered');
				})
			}
		});

		$('#qty').keyup(function(e) {
			if (e.keyCode == 13) {
				$.ajax({
					type: 'POST',
					url: '<?php echo site_url('admin/check_qty') ?>',
					data: {bCode: $('#barcode').val(), date: $('#drDate').val(), lotNo : $('#lotNo').val(), qty: $(this).val(), number: $('#drNumber').val(), toggle: $('#toggle-type').prop('checked'), co_line: $('#co_line').val()},
					dataType: 'json'
				}).then(function(data) {
					if (data == '' || data == null) {
						$('#qty').parent('div').addClass('has-error');
						$('#qty').val('');
						toastr.error('Quantity Defined in the System Doesn\'t Match');
					}
					else {
						$('#qty').parent('div').removeClass('has-error').addClass('has-success');
						$('#qty').focus();

						if (data.status == 'exist') {
							if ($('#toggle-type').prop('checked') == false) {
								toastr.info('Item Already Scanned', 'Item');
								resetInput();
							} else {
								toastr.success('Item Information Matched To The DR List');
								resetInput();
							}
							// toastr.info('Item Already Scanned');
						} else {
							toastr.success('Item Information Matched To The DR List');

							return $.post('<?php echo site_url('admin/change_item_status') ?>', {date: $('#drDate').val(), barcode: $('#barcode').val(), lotNo: $('#lotNo').val(), qty: $('#qty').val(), toggle: $('#toggle-type').prop('checked'), co_line: $('#co_line').val(), number: $('#drNumber').val()}, function() {
								$('form :input').parent('div').removeClass('has-error').removeClass('has-success');
									$('#barcode').val('');
									$('#lotNo').val('');
									$('#qty').val('');
									$('#barcode').focus();
									$('#lotNo').attr('disabled', false);
							}, 'json');
						}
					}
				}).then(function() {
					return $.post('<?php echo site_url('admin/show_dr_by_date') ?>', {date: $('#drDate').val(), number: $('#drNumber').val(), toggle: $('#toggle-type').prop('checked')},
						function(data) {
							var ctr = 0;
							$('#drList tbody').empty();
							$.each(data, function(index, elem) {
								ctr++;
								$('#drList tbody').append('<tr><td>'+elem.description+'</td><td>'+elem.lotNo+'</td><td>'+elem.qty+'</td><td>'+elem.img+'</td></tr>');
							});

							// for automatic generation of report disabled 04/07/2017
							// var date = $('#drDate').val();
							// var number = $('#drNumber').val();
							
							// if ($("table img").length == parseInt($('input[name="list-count"]').val())) {
							// 	window.open('<?php echo site_url('admin/dr_summary') ?>/?date='+date+'&drNumber='+number);
							// }
						}, 'json');
				}).fail(function() {
					toastr.error('Error in admin/check_qty.<br>Please contact MIS');
				})
			}
		});

		function reloadTable() {
			return $.ajax({
				url: '<?php echo site_url('admin/show_dr_by_date') ?>',
				type: 'POST',
				data: {date: $('#drDate').val(), toggle: $('#toggle-type').prop('checked')}, 
				dataType: 'json'
			})
		}

		function checkIfItemExist(itemCode) {
			var val = $('td:contains('+itemCode+')').text();
			$('td:contains('+itemCode+')').parent('tr').append('<td><img src="<?php echo base_url("assets/images/Ok-icon.png") ?>" width="15px"></td>');
		}
	});
</script>