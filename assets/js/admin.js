( function( $ ) {
	"use strict";

	Unzer.prototype.init = function() {
		// Add event handlers
		this.actionBox.on( 'click', '[data-action]', $.proxy( this.callAction, this ) );
		this.actionBox.on( 'keyup', ':input[name="unzer_split_amount"]', $.proxy( this.balancer, this ) );
	};

	Unzer.prototype.callAction = function( e ) {
		e.preventDefault();
		var target = $( e.target );
		var action = target.attr( 'data-action' );

		if( typeof this[action] !== 'undefined' ) {
			var message = target.attr('data-notify') || 'Are you sure you want to continue?';
			if( confirm( message ) ) {
				this[action]();
			}
		}
	};

	Unzer.prototype.capture = function() {
		var request = this.request( {
			Unzer_action : 'capture'
		} );
	};

	Unzer.prototype.cancel = function() {
		var request = this.request( {
			Unzer_action : 'cancel'
		} );
	};

	Unzer.prototype.refund = function() {
		var request = this.request( {
			Unzer_action : 'refund'
		} );
	};

	Unzer.prototype.split_capture = function() {
		var request = this.request( {
			Unzer_action : 'splitcapture',
			amount : parseFloat( $('#Unzer_split_amount').val() ),
			finalize : 0
		} );
	};

	Unzer.prototype.split_finalize = function() {
		var request = this.request( {
			Unzer_action : 'splitcapture',
			amount : parseFloat( $('#Unzer_split_amount').val() ),
			finalize : 1
		} );
	};

	Unzer.prototype.request = function( dataObject ) {
		var that = this;
		var request = $.ajax( {
			type : 'POST',
			url : ajaxurl,
			dataType: 'json',
			data : $.extend( {}, {
                action : 'unzer_manual_transaction_actions',
                Unzer_transaction_id: that.actionBox.attr('data-transaction-id'),
                Unzer_log_id: that.actionBox.attr('data-log-id')
            }, dataObject ),
			beforeSend : $.proxy( this.showLoader, this, true ),
			success : function() {
				$.get( window.location.href, function( data ) {
					var newData = $(data).find( '#' + that.actionBox.attr( 'id' ) + ' .inside' ).html();
					that.actionBox.find( '.inside' ).html( newData );
					that.showLoader( false );
				} );
			}
		} );

		return request;
	};

	Unzer.prototype.showLoader = function( e, show ) {
		if( show ) {
			this.actionBox.append( this.loaderBox );
		} else {
			this.actionBox.find( this.loaderBox ).remove();
		}
	};

	Unzer.prototype.balancer = function(e) {
		var remainingField = $('.unzer-remaining');
		var balanceField = $('.unzer-balance');
		var amountField = $(':input[name="unzer_split_amount"]');
		var btnCaptureSplit = $('#Unzer_split_button');
		var btnSplitFinalize = $('#Unzer_split_finalize_button');
		var amount = parseFloat(amountField.val().replace(',','.'));

		if( amount > parseFloat(remainingField.text()) || amount <= 0 || isNaN(amount) || amount == '') {
			amountField.addClass('warning');
			btnCaptureSplit.fadeOut().prop('disabled', true);
			btnSplitFinalize.fadeOut().prop('disabled', true);
		} else {
			amountField.removeClass('warning');
			btnCaptureSplit.fadeIn().prop('disabled', false);
			btnSplitFinalize.fadeIn().prop('disabled', false);
		}
	};

	// DOM ready
	$(function() {
		new Unzer();
	});

	function Unzer() {
		this.actionBox 	= $( '#unzer-payment-actions' );
		this.postID		= $( '#post_ID' );
		this.loaderBox 	= $( '<div class="loader"></div>');
		this.init();
	}

})(jQuery);
