<?php

/**
* 
*/
class Syteline_model extends CI_Model
{
	
	function __construct()
	{
		parent::__construct();
		$this->load->database('syteline');
		$this->load->dbutil();
		
	}

	function import($date) {
		$query = $this->db->query("
			SELECT distinct
do_hdr.do_hdr_date,
do_hdr.do_num,
custaddr.name,
do_seq.ref_num,
co_ship.do_line AS ref_line,
--do_seq.ref_line, 
coitem.item,
coitem.description,
matltrack.lot,
co_ship.uf_qtyshipconv,
coitem.u_m,
item.lot_tracked 

FROM do_hdr
left join do_seq on do_seq.do_num = do_hdr.do_num
left join co_ship on co_ship.do_num = do_seq.do_num and co_ship.do_line = do_seq.do_line and co_ship.do_seq = do_seq.do_seq
--left join do_seq on do_seq.do_num = do_hdr.do_num
left join coitem on coitem.co_num = co_ship.co_num and coitem.co_line = co_ship.co_line
left join custaddr on custaddr.cust_num = do_hdr.cust_num and custaddr.cust_seq = do_hdr.cust_seq
left join matltrack on matltrack.ref_num =  do_seq.ref_num and matltrack.ref_line_suf = do_seq.ref_line and matltrack.trans_date = do_seq.ship_date and matltrack.date_seq = do_seq.date_seq
left join item on item.item = coitem.item

--left join coitem on coitem.co_num = co_ship.co_num and coitem.co_line = co_ship.co_line
--where --do_hdr.do_hdr_date between '2017-03-23 00:00:00.000' and '2017-03-23 00:00:00.000'
--co_ship.do_num ='DR-000000000000000000000103870' 

			where do_hdr.do_hdr_date = '".date('Y-m-d', strtotime($date))." 00:00:00.000' 
			AND (do_hdr.stat = N'A')"
		);

		// die(var_dump($this->db->last_query()));


		// $delimiter = ",";
		// $newline = "\r\n";
		// $enclosure = '"';

		// return $sqlToCSV = $this->dbutil->csv_from_result($query, $delimiter, $newline, $enclosure);
		

		return $query->result();
	}

	public function getMatltrack($item, $date)
	{
		$query = $this->db->query("SELECT * FROM matltrack WHERE item='$item' AND trans_date='$date'");

		return $query->result();
	}
}