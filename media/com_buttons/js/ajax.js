jQuery(document).ready(function($)
{
	$(".com_buttons").mouseleave(function(e)
	{
		var $self = $(this);
		$self.removeClass("pressing");
	});


	$(".com_buttons").click(function(e)
	{
		e.preventDefault();
		var $self = $(this);
		if (!$self.hasClass("readonly"))
		{
			$self.addClass("pressing");
			if (Joomla.JDEBUG && console && console.log) console.log(this.href);
			var val = this.href.substr(this.href.indexOf('?') + 1);
			if (Joomla.JDEBUG && console && console.log) console.log(val);

			params = this.href.substr(this.href.indexOf('?') + 1).split('&');
			if (Joomla.JDEBUG && console && console.log) console.log(params);

			var data = {
				'format':'json',
				'task':'button.click',
				'asset_id':0,
				'catid':0,
				'id':0
				};

			for (var i = 0, len = params.length; i < len; i++) {
				var pair = params[i].split('=');
				data[pair[0]] = pair[1];
			}

			if (Joomla.JDEBUG && console && console.log) console.log(data);

			var href = this.href.substr(0, this.href.indexOf("?"));

			jQuery.post(href, data)
			.done(function(data) {
				if (Joomla.JDEBUG && console && console.log) console.log(data);
				var response = jQuery.parseJSON(data);
				if (response.success)
				{
					$.each(response.buttons, function(index, item) {
		        		if (Joomla.JDEBUG && console && console.log)
		        		{
				        	if (item.value > 0)
				        		msg = 'enabling...';
		        			else
				        		msg = 'diabling...';
				        	msg = msg + ' ('
				        		+'asset_id: '+response['asset_id']
				        		+' - catid: '+response['catid']
				        		+' - id: '+item.id
				        		+' - value: '+item.value
				        		+')';
		        			console.log(msg);
		        		}
			        	if (item.value > 0)
			        	{
			        		$("#buttons-top-"+response['asset_id']+'-'+response['catid']).find(".button-"+item.id).addClass("active");
			        		$("#buttons-bottom-"+response['asset_id']+'-'+response['catid']).find(".button-"+item.id).addClass("active");
							/* $(".button-"+response['asset_id']+'-'+item.id).removeClass("active"); */
			        	}
			        	else
			        	{
			        		$("#buttons-top-"+response['asset_id']+'-'+response['catid']).find(".button-"+item.id).removeClass("active");
			        		$("#buttons-bottom-"+response['asset_id']+'-'+response['catid']).find(".button-"+item.id).removeClass("active");
							/*  $(".button-"+response['asset_id']+'-'+item.id).removeClass("active"); */
			        	}
			        	if (response['readonly'])
			        	{
			        		$(".button-"+response['asset_id']+'-'+item.id)
			        			.addClass("readonly")
			        			.off('mouseleave');
			        	}
					});
				}
				else
				{
					alert(response.error);
				}
			})
			;
		}
	});
});
