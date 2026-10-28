<?php
/**
 * Creates clear, consistent MailerLite group names for Gig RSVPs.
 */

class BVOCAL_Gig_RSVP_Group_Naming {
	public static function make( $title, $date ) {
		$title = trim( (string) $title );
		$date  = trim( (string) $date );

		return sprintf(
			'Gig RSVP - %s - %s',
			$title ? $title : 'Untitled Gig',
			$date ? $date : 'Date TBD'
		);
	}
}
