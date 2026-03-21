function simility(){
	var sc = {
		"customer_id": agerede.antifraud.customer_id,
		"session_id": agerede.antifraud.session_id,
		"event_types": agerede.antifraud.event_types,
		"zone": agerede.antifraud.zone,
		"request_endpoint": agerede.antifraud.request_endpoint
	};

	var ss = new SimilityScript(sc);
	ss.execute();
};