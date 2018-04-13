<?php

if ($argc != 3) exit("Please specify input files for parsing.\n");

$entity_data_file_handle = fopen($argv[1], 'r');
$terms = get_terms($entity_data_file_handle);
fclose($entity_data_file_handle);

$parent_child_data_file_handle = fopen($argv[2], 'r');
parse_parent_child_data($terms, $parent_child_data_file_handle);
fclose($parent_child_data_file_handle);

$output_file = "ipr.obo";
$output = "format-version: 1.2\ndefault-namespace: interpro ontology\n\n";
// Print out all the terms
foreach ($terms as $key => $value) {
  $output .= "[Term]\n";
  $output .= "id: $key\n";
  $output .= 'name: ' . $value['name'] . "\n";
  foreach ($value['parents'] as $parent) {
    if ($parent) $output .= "is_a: $parent\n";
  }
  $output .= "\n";
}

file_put_contents($output_file, $output);


/*** Output annotated terms ***/

/**
 * Converts raw domain data into a list of terms.
 *
 * @param $domain_data_file_handle
 *  Open file containing raw data on domains for conversion
 *
 * @return $terms
 *  Array of converted terms
 *
 */
function parse_parent_child_data(&$terms, $domain_data_file_handle)
{
  $parents = [];
  $parent = '';

  while(!feof($domain_data_file_handle)) {
    $line = fgets($domain_data_file_handle);
    if (stripos($line, 'ipr') === false) continue;
    $substrings = explode('::', $line);
    $accession = $substrings[0];

    // If the first character in accession is not a dash, we have a domain
    if ($accession[0] != '-') {
      $parents = [];
      $parent = '';
      $parents[0] = trim($accession, '-');
    }

    // Get parent
    $i = 0;
    while ($accession[$i * 2] == '-') {
      if (!array_key_exists($i + 1, $parents)) {
        $parents[$i + 1] = trim($accession, '-');
      }
      $parent = $parents[$i];
      $i++;
    }
    $accession = trim($accession, '-');

    if (isset($terms[$accession])) {
      $terms[$accession]['parents'][] = $parent;
      continue;
    }
  }

  return $terms;
}

/**
 * Add all IPR terms.
 *
 * @param $entity_data_file_handle
 *  Open file containing raw entity data containing accessions and names
 *
 * @return $terms
 *  Array of terms
 *
 */
function get_terms($entity_data_file_handle)
{
  $terms = [];

  while(!feof($entity_data_file_handle)) {
    $line = fgets($entity_data_file_handle);
    if (stripos($line, 'ipr') === false) continue;
    $substrings = explode("\t", $line);
    $accession = $substrings[0];
    $name = trim($substrings[2], "\n");

    if (!isset($terms[$accession])) {
      $term = [
        'accession' => $accession,
        'name' => $name,
        'parents' => []
      ];

      $terms[$accession] = $term;
    }
  }

  return $terms;
}