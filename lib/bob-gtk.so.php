<?php

/*//.
	Bob's GTK Library
 	Version: 1.0.0
	Date: 2012/02/29
	License: 2-Clause BSD (see below)

	This library provides a set of useful widgets to help remove you from some
	of the most annoying parts of GTK while developing applications. Need an
	error dialog? This has things like that, preconfigured to behave like you
	would expect so you do not have to.

	And of course, you can extend these yourself even further. A lot of them are
	so simple and yet such a pain in the ass to do every day yourself.
.//*/


/*******************************************************************************
********************************************************************************

Copyright (c) Bob Majdak Jr 2012 All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

* Redistributions of source code must retain the above copyright notice, this
  list of conditions and the following disclaimer.

* Redistributions in binary form must reproduce the above copyright notice, this
  list of conditions and the following disclaimer in the documentation and/or
  other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

********************************************************************************
*******************************************************************************/

/*//. function bob_gtk_object_default($in,$def)

	given an object or array as input, and an object or array which contains
	the properties the input HAS to have, force the input to be an object with
	at least the default properties.

	tbh, this is just a utility function for the rest of the library.

.//*/

function bob_gtk_object_default($input,$defaults) {
	if(is_object($defaults)) $defaults = (array)$defaults;
	if(!is_array($defaults)) return false;

	if(is_array($input)) $input = (object)$input;
	if(!is_object($input)) return (object)$defaults;

	foreach($defaults as $key => $value) {
		if(!property_exists($input,$key)) {	
			$input->{$key} = $value;
		}
	}

	return $input;
}

function bob_gtk_events_pending() {
	while(Gtk::events_pending())
		Gtk::main_iteration();

	return;
}

/*//.
	class bobWindow([$opt]) (extends GtkWindow)

	provide a basic window that does these things by default:
 	- display in the center of the screen.
 	- provide a vbox for packing (like GtkDialog does)
 	- able to handle killing itself for the most basic app. extend this class
 	  and overwrite my onDelete method if you need to change what happens when
 	  the window is closed. by default, just kills off the app.

 	$opt = options object or array
	$opt->parent = the main window of your application that this dialog should
	   be associated with. this is important for the OS to properly handle
	   taskbar entries and focus management.
	$opt->modal = [boolean] set if the window should become modal.
	$opt->title = [string] set the title of the window.
	$opt->size = [string] set the size of the window in WIDTHxHEIGHT format.
	$opt->iconpath [string] a directory to look for icon files in.

.//*/

class bobWindow extends GtkWindow {

	public function __construct($opt=array()) {
		parent::__construct();
		$opt = bob_gtk_object_default($opt,array(
			'parent'   => null,
			'modal'    => false,
			'title'    => 'Hello World',
			'size'     => '300x300',
			'iconpath' => sprintf('%s/rsrc',dirname(dirname(__FILE__)))
		));

		$this->set_position(Gtk::WIN_POS_CENTER);

		if($opt->modal) $this->set_modal(true);
		if($opt->parent) $this->set_transient_for($opt->parent);
		if($opt->title) $this->set_title($opt->title);

		// if the size was defined, try to set the size. it should allow string
		// representations of WIDTHxHEIGHT and arrays.
		if($opt->size) {
			if(is_string($opt->size)) {
				list($w,$h) = explode('x',$opt->size);
				$this->set_size_request($w,$h);
				unset($w,$h);
			}

			else if(is_array($opt->size)) {
				// TODO
			}
		}

		// if a path for the icons was defined try and build a list of the
		// icons available for the window to use.
		if($opt->iconpath && is_dir($opt->iconpath)) {
			$iconlist = glob("{$opt->iconpath}/icon-*.png");

			$iconbufs = array();
			foreach($iconlist as $iconfile)
				$iconbufs[] = GdkPixbuf::new_from_file($iconfile);

			// if(count($iconbufs)) $this->set_icon_list($iconbufs); // not implemented yet
			unset($iconlist,$iconbufs,$iconfile);
		}

		$this->connect('delete-event',array($this,'onDelete'));

		$this->vbox = new GtkBox(GtkOrientation::VERTICAL);
		$this->add($this->vbox);

		return;
	}

	public function onDelete() {
		Gtk::main_quit();
		return;
	}

}

/*//.
	class bobDialog([$opt]) (extends GtkDialog)

	provide a basic dialog window that does these things by default:
	- display in the center of the screen.
	- assume control of the focus, not allowing you to click the other windows
	  in the app until the dialog is dismissed.

	$opt = options object or array (optional)
	$opt->parent = the main window of your application that this dialog should
	   be associated with. this is important for the OS to properly handle
	   taskbar entries and focus management.

.//*/

class bobDialog extends GtkDialog {

	public function __construct($opt=array()) {
		parent::__construct();
		$opt = bob_gtk_object_default($opt,array(
			'parent' => null,
			'modal'  => true
		));

		$this->set_position(Gtk::WIN_POS_CENTER);

		if($opt->modal) $this->set_modal(true);
		if($opt->parent) $this->set_transient_for($opt->parent);

		$this->connect('response',array($this,'onResponse'));

		return;
	}

	public function onResponse($w,$r) {
		$this->hide();
		return;
	}

}

/*//. class bobConfirmDialog([$opt]) (extends bobDialog)

	provide a dialog that requires the user to answer yes or no.
	- see also: bobDialog

	$opt = options object or array.
	$opt->title = text for the dialog title bar.
	$opt->message = message for the dialog. you should be asking the user a
	   question they can answer with "yes" or "no"

.//*/

class bobConfirmDialog extends bobDialog {

	public function __construct($opt=array()) {
		parent::__construct($opt);
		$opt = bob_gtk_object_default($opt,array(
			'title' => 'Confirm',
			'message' => 'some-question-goes-here'
		));

		$this->set_title($opt->title);

		$this->label = new GtkLabel($opt->message);
		$this->vbox->pack_start($this->label);

		$this->add_button('Yes',Gtk::RESPONSE_YES);
		$this->add_button('No',Gtk::RESPONSE_NO);

		$this->show_all();
		return;
	}

}

/*//. class bobAlertDialog([$opt]) (extends bobDialog)

	provide a dialog that requires the user to acknowledge to proceed. 
	- see also: bobDialog

	$opt = options object or array.
	$opt->title = text for the dialog title bar.
	$opt->message = message for the dialog. you should be asking the user a
	   question they can answer with "yes" or "no"

.//*/

class bobAlertDialog extends bobDialog {

	public function __construct($opt=array()) {
		parent::__construct($opt);
		$opt = bob_gtk_object_default($opt,array(
			'title' => 'Alert',
			'message' => 'some-message-goes-here'
		));

		$this->set_title($opt->title);

		$this->label = new GtkLabel($opt->message);
		$this->label->set_max_width_chars(64);
		$this->vbox->pack_start($this->label);

		$this->add_button('OK',Gtk::RESPONSE_OK);

		$this->show_all();
		return;
	}

}

/*//. class bobOpenFileDialog([$opt]) (extends GtkFileChooserDialog)

	provide a dialog suitable for selecting a file to open. also works with
	bobSaveFileDialog to try and recall the last directory viewed so the next
	time it is used it opens where the user left it.

	$opt = options object or array.
	$opt->parent = the main window of your application that this dialog should
	   be associated with. this is important for the OS to properly handle
	   taskbar entries and focus management.

.//*/

class bobOpenFileDialog extends GtkFileChooserDialog {

	static $lastdir = null;

	public function __construct($opt=array()) {
		$opt = bob_gtk_object_default($opt,array(
			'parent' => null,
			'action' => 'file'
		));

		// determine what type of open dialog we wanted.
		switch($opt->action) {
			case 'folder': {
				$type = GtkFileChooserAction::SELECT_FOLDER;
				$title = 'Open Folder...';
				break; 
			}
			case 'file': { }
			default: {
				$type = GtkFileChooserAction::OPEN;
				$title = 'Open File...';
				break;
			}
		}

		// go ahead and construct now.
		parent::__construct($title, $opt->parent, $type, [
			"Cancel", GtkResponseType::CANCEL, 
			"Accept", GtkResponseType::ACCEPT, 
		]);

		if(!self::$lastdir) {
			if(class_exists('bobSaveFileDialog') && bobSaveFileDialog::$lastdir)
			self::$lastdir = bobSaveFileDialog::$lastdir;
		}

		// go to the last used directory if one was set.
		if(self::$lastdir) $this->set_current_folder(self::$lastdir);

		// $this->set_action($type);
		// $this->set_modal(true);
		$this->set_position(Gtk::WIN_POS_CENTER);
		// $this->set_title($title);
		// if($opt->parent) $this->set_transient_for();

		// $this->add_buttons(array(
		// 	Gtk::STOCK_CANCEL, Gtk::RESPONSE_CANCEL,
		// 	Gtk::STOCK_OPEN, Gtk::RESPONSE_ACCEPT
		// ));

		$this->show_all();
		$result = $this->run();
		$this->hide();

		// if ok keep the file in a property on this object.
		if($result == GtkResponseType::ACCEPT) $this->file = $this->get_filename();
		else $this->file = null;

		// remember the last used folder so we can go there next time we ask
		// to open a file.
		self::$lastdir = $this->get_current_folder();

		return;
	}

}

/*//. class bobSaveFileDialog([$opt]) (extends GtkFileChooserDialog)

	provide a dialog suitable for selecting where to save a file. also works
	with bobOpenFileDialog to try and recall the last directory viewed so the
	next time it is used it opens where the user left it.

	$opt = options object or array.
	$opt->parent = the main window of your application that this dialog should
	   be associated with. this is important for the OS to properly handle
	   taskbar entries and focus management.
	$opt->protectOverwrite = bool true or false. if true, and the selected file
	   already exists it will show a confirm dialog asking if the user is sure.
	   saying no will allow them to select another file.

.//*/

class bobSaveFileDialog extends GtkFileChooserDialog {

	static $lastdir = null;

	public function __construct($opt=array()) {
		parent::__construct(Gtk::FILE_CHOOSER_ACTION_SAVE);
		$opt = bob_gtk_object_default($opt,array(
			'parent'           => null,
			'protectOverwrite' => true
		));

		// check if the open dialog had a directory.
		if(!self::$lastdir) {
			if(class_exists('bobOpenFileDialog') && bobOpenFileDialog::$lastdir)
			self::$lastdir = bobOpenFileDialog::$lastdir;
		}

		// go to the last used directory if one was set.
		if(self::$lastdir) $this->set_current_folder(self::$lastdir);

		$this->set_type(Gtk::FILE_CHOOSER_ACTION_SAVE);
		$this->set_modal(true);
		$this->set_position(Gtk::WIN_POS_CENTER);
		$this->set_title('Save File...');
		if($opt->parent) $this->set_transient_for($opt->parent);

		// create cancel and accept buttons.
		$this->add_buttons(array(
			Gtk::STOCK_CANCEL, Gtk::RESPONSE_CANCEL,
			Gtk::STOCK_SAVE, Gtk::RESPONSE_ACCEPT
		));

		$this->show_all();


		// run the save dialog in a loop, that way if protectOverwrite is
		// enabled we can allow the user to try another file if they selected
		// the wrong one.
		$run = true;
		while($run) {
			$result = $this->run();

			// if ok keep the file in a property on this object.
			if($result == Gtk::RESPONSE_ACCEPT) $this->file = $this->get_filename();
			else $this->file = null;

			// check if the file exists and warn.
			if($this->file)
			if($opt->protectOverwrite && file_exists($this->file)) {
				$dialog = new bobConfirmDialog(array(
					'parent'  => $this->get_transient_for(),
					'title'   => 'Overwrite?',
					'message' => 'This file already exists. Overwrite it?'
				));

				if($dialog->run() == Gtk::RESPONSE_YES)
					$run = false;

				continue;
			} else {
				$run = false;
				continue;
			}
		}

		$this->hide();

		// remember the last used folder so we can go there next time we ask
		// to open a file.
		self::$lastdir = $this->get_current_folder();

		return;
	}

}

/*//. class bobTextView(void) extends GtkScrolledWindow

	provide a COMPLETE "Text View" widget to use. it creates a text view in a
	scrolled window that scrolls just like textarea's do on webpages. the scroll
	bars only display when they are needed. also provides methods for getting
	all and setting the text without having to deal with the buffer directly
	for basic operations.

	+ string get_text(void)
	  return all the text in this text area.

	+ void set_text(string)
	  clear the text area and set it to the text you specify.

.//*/

class bobTextView extends GtkScrolledWindow {

	public $view;
	public $buffer;

	public function __construct() {
		parent::__construct();

		$this->set_policy(Gtk::POLICY_AUTOMATIC,Gtk::POLICY_AUTOMATIC);
		$this->set_shadow_type(Gtk::SHADOW_ETCHED_IN);

		$this->view = new GtkTextView(new bobTextBuffer);
		$this->buffer = $this->view->get_buffer();

		$this->add($this->view);
		return;
	}

	public function get_text() {
		return $this->buffer->get_full_text();
	}

	public function set_text($input) {
		$this->buffer->set_text($input);
		return;
	}

	public function append_text($input,$scroll=true) {

		$this->buffer->place_cursor($this->buffer->get_end_iter());
		$this->buffer->insert_at_cursor($input);
		bob_gtk_events_pending();

		$this->view->scroll_to_iter($this->buffer->get_end_iter(),0);
		bob_gtk_events_pending();

		return;
	}

}

/*//. class bobTextBuffer(void) extends GtkTextBuffer

	provide a customized GtkTextBuffer that is a bit easier to use.

	+ string get_full_text(void);
	  return everything in the text buffer without having to deal with the
	  text iterators yourself.

.//*/

class bobTextBuffer extends GtkTextBuffer {

	public function get_full_text() {
		return $this->get_text(
			$this->get_start_iter(),
			$this->get_end_iter()
		);
	}

}