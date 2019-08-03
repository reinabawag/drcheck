
	<?php echo form_open() ?>
		    	<!-- <div class="form-group">
		    		<label for="drDate" class="control-label">DR DATE</label>
		    		<input type="text" name="" id="drDate" class="form-control" maxlength="0" placeholder="PLEASE SELECT DATE">
		    	</div>
		    	<div class="form-group">
		    		<label for="drNumber" class="control-label">DR NUMBER</label>
		    		<select name="drNumber" class="form-control" id="drNumber" placeholder="DR NUMBER">
		    		</select>
		    	</div> -->
		    	<div class="form-group">
		    		<label for="barcode" class="control-label">ITEM CODE</label>
		    		<input type="text" name="test" class="form-control" id="barcode">
		    		<p id="count" style="display: none;"></p>
		    		<!-- <input type="hidden" name="box" id="box" value="" ctr=0>
		    		<input type="hidden" name="noLot" id="noLot">
		    		<input type="hidden" name="tmpItemCode" id="tmpItemCode">
		    		<input type="hidden" name="co_line" id="co_line"> -->
		    	<!-- </div> -->
		    	<!-- <div class="form-group" align="">
		    		<label class="checkbox"> -->
		    		<!-- <?php echo $this->session->is_supervisor ? 'checked' : null ?> -->
		    			<!-- <input type="checkbox"  data-toggle="toggle" id="toggle-type" data-on="RECHECK" data-off="NORMAL" data-onstyle="success" data-offstyle="primary" name="toggle-status">  -->
		    			<!-- <span id="span-toggle">Normal</span> -->
		    		<!-- </label> -->
		    	<!-- </div> -->
		    	<!-- <div class="form-group">
		    		<label for="lotNo" class="control-label">LOT NO.</label>
		    		<input type="text" name="" class="form-control" id="lotNo" placeholder="LOT NO.">
		    	</div> -->
		    	<!-- <div class="form-group">
		    		<label for="qty" class="control-label">QTY</label>
		    		<input type="text" name="" class="form-control" id="qty" placeholder="QTY">
		    	</div> -->
		    	<input type="text" name="test2" id="hahaha">
		    	<!-- <button class="btn btn-warning" type="reset" id="reset">RESET</button> -->
		    	<?php echo form_close()	 ?>
</div>

	<script  type="text/javascript">
		$(document).ready(function(){

			$('#barcode').keyup(function(e) {
				if (e.keyCode == 13) {
					$.post("<?php echo site_url('main/ajax_test') ?>", $('form').serialize(), function(data) {
						console.log(data);
					}, 'json')
				}
				
			});
		});
	</script>