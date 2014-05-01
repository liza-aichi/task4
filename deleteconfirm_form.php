<?php
/**
 * confirm delete form
 * @param $form
 * @param $form_state
 * @param null $argu
 * @return array
 */

function delete_confirm($form, $form_state, $argu = NULL)
{

    $form = array();

    $question = "Confirm deleting this contatcs?";
    $path = 'address-book/list_form';
    $ids = array();
    if ($argu != "") {
        $description = 'ddd';
        $result = db_select('contact', 'c');
        $result->fields('c', array('contact_id', 'first_name', 'last_name'));


        $result->condition('contact_id', $argu, '=');
        $result_rows = $result->execute()->fetchAll();
        $description = '<ul>';
        $description .= '<li>' . $result_rows[0]->first_name . ' '
            . $result_rows[0]->last_name . '</li>';


        $description .= '</ul>';
        $ids[$argu] = $argu;

    } else if (isset($_SESSION['args'])) {
        $contacts = $_SESSION['args'];


        $description = '<ul>';
        foreach ($contacts as $key => $contact) {
            $description .= '<li>' . $contact . '</li>';
            $ids[$key] = $key;

        }
        $description .= '</ul>';

    } else $description = '';

    $form['ids'] = array(
        '#type' => 'value',
        '#value' => $ids
    );


    $yes = 'Delete';
    $no = 'Cancel';
    $name = 'confirm';
    // Display form confirmation


    //  }
    if (isset($_SESSION['args']) || $argu != "") $form = confirm_form($form, $question, $path, $description, $yes, $no, $name);
    else drupal_goto('address-book');
    return $form;
}

/**
 * delete confirm form submit
 *
 */

function delete_confirm_submit(&$form, &$form_state)
{
    drupal_set_message(t('Contacts were deleted'));
    $ids = $form_state['values']['ids'];


    $orid = db_or();
    foreach ($ids as $id) {
        $result = db_select('contact', 'c');
        $result->join('file_managed', 'f', 'f.fid = c.img_id');
        $result->fields('c', array('contact_id', 'img_id'));
        $result->fields('f', array('fid', 'uri'));

        $result->condition('contact_id', $id, '=');
        $result_rows = $result->execute()->fetchAll();
        if (isset($result_rows[0])) {
            $or = db_or();
            $or->condition('fid', $result_rows[0]->img_id, '=');
            $file = file_load($result_rows[0]->fid);

            file_unmanaged_delete($file->uri);
        }
        $orid->condition('contact_id', $id, '=');
    }


    if (isset($or)) {
        $result = db_delete('file_managed');
        $result->condition($or)->execute();
    }
    db_delete('contact')->condition($orid)->execute();
    drupal_goto('address-book');


}