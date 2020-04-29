    if(typeof window.confirm_link == "undefined") {
        function confirm_link(message, url) {
        	if (confirm(message)) {
        		location.href = url;
        	}
        }
    }