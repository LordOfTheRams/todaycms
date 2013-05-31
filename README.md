#API
The api is the server endpoint for creating, reading, updating and deleting objects from collections in the database.

Authentication
======

All calls to the api must include a _token parameter to validate the call.

	http://todaycms-api.herokuapp.com/?_token=XXXXXXXXXXXX

This guide will not include the _token in the sample calls. It is implied that it must be included.

##Example Config
The following collection config will be used as the config throughout the example calls in this guide.

	{
		"team": {
			"title": "Team Members",
			"type": "multiple",
			"fields": {
				"name": {
					"title": "Name",
					"type": "text"
				},
				"employment": {
					"title": "Employment Status",
					"type": "select",
					"options": {
						"half": "Part Time",
						"full": "Full Time",
						"retired": "Retired"
					}
				},
				"bio": {
					"title": "Bio",
					"type": "textarea"
				},
				"address": {
					"title": "Home Address",
					"type": "location"
				}
			}
		}
	}

Create
======

To create an object in a collection, issue a HTTP POST call with the object in the body of the POST.

	GET /collections/:collection

**URL Parameters**

`:collection` is the key of the collection in the config.

**Example**

	POST /collections/team

	name = Justin
	employment = full
	bio = Node.js developer
	address[city] = Bismarck
	address[state] = ND
	address[zip] = 58501
	...

Note: 'team' is the key used in our example config at the top of this guide

**Returns**

The object you just created.

Read
======

To read from a collection, issue a HTTP GET call

	GET /collections/:collection


**URL Parameters**

`:collection` is the key of the collection in the config.

**Returns**

An array of objects from the collection.

##Filtering
To filter an api call, send the optional `filter` parameter. The filter parameter is a structured json object. Examples use the following terms:

* field key - Is the key used to define the field in the config.
* operator - Is the comparison to preform.
* value - Is what we will compare the object value to.

**Basic Structure:**

This is the most basic filter that can be preformed.

	{
		"field key":  "value"
	}

*Example Call:*

	GET /collections/team?filter={"employment":"full"}

This will find all full time team members in the collection.

**Advanced Structure:**

This structure allows more advanced control over operators used when filtering.

	{
		"field key": {
			"operator": "value"
		}
	}

*Example Call:*

	GET /collections/team?filter={"employment": {"!=": "retired"}}

This will find all team members who are not retired in the collection.

**Multiple Operators Structure:**

You can preform multiple comparision operations on the same field

	{
		"field key": {
			"operator": "value",
			"operator2": "value2",
			"operatorx..": "valuex.."
		}
	}

*Example Call:*

	GET /collections/team?filter={"name": {"LIKE":"Justin%", "!=":"Justin Walsh"}}

This will find all team members who's names start with 'Justin' but are not 'Justin Walsh'.

**Multiple Fields Structure**

You can filter multiple fields by adding each field key to the object.

	{
		"field key 1": "value",
		"field key 2": {
			"operator": "value"
		},
		"field key 3": {
			"operator": "value"
		}
	}

**Multiple Values Structure**

When filtering, you can use an array instead of a string to match any of the values in the array.

	{
		"employment": ["full", "part"],
		"name": {
			"LIKE": ["Justin%", "Brian%"]
		}
	}

This will match all team members who are full or part time, and whose names starts with 'Justin' or 'Brian'.

Note: `"employment": ["full", "part"]` is equvalient to

	"employment": {
		"=": ["full", "part"]
	}

**Nested Fields Structure**

You can filter by nested fields in the object using a dot notation syntax as the field key in the filter. This syntax can be used to query deep into nested objects and arrays, including fields like the 'multi'.

	{
		"address.zip": "58501"
	}

This will filter the location field type by zip code, which is a nested field. This example is based on the sample config at the top of this guide.

**AND/OR Fields Structure (Not Supported Yet)**

This structure allows a very customized query including both `-and` and `-or` statments.

	{
		"field key 3": {
			"operator": "value"
		},
		"-or": {
			"field key 1": "value",
			"-and": {
				"field key 2": {
					"operator": "value"
				},
				"field key 4": {
					"operator": "value"
				}
			}
		}
	}


**Supported Operators**

| Operator | Description |
| :---: | --- |
| = | equal to |
| != | not equal to |
| > | greater than |
| < | less than |
| >= | greater than or equal to |
| <= | less than or equal to |
| LIKE | case sensitive search, use '%' for wildcard |
| ILIKE | case insensitive search, use '%' for wildcard |
| NOT LIKE | inverse of 'LIKE' |
| NOT ILIKE | inverse of 'ILIKE' |

**JSON Filtering**

The JSON object used for the `filter` parameter must be url encoded when calling the api. Here are examples of how to url encode a json object or string in different languages.

***php***

	$escaped_json = urlencode('{"name": {"LIKE":"Justin%", "!=":"Justin Walsh"}}');

***javascript string***

	var escaped_json_from_string = escape('{"name": {"LIKE":"Justin%", "!=":"Justin Walsh"}}');

***javascript object***

	var escaped_json_from_object = escape(JSON.stringify({"name": {"LIKE":"Justin%", "!=":"Justin Walsh"}}));

Update
======

To update an object in a collection, issue a HTTP POST call with the object in the body of the POST.

	POST /collections/:collection/:id

	name = Justin Walsh
	employment = part
	bio = Documention writer
	...

**URL Parameters**

`:collection` is the key of the collection in the config.

`:id` is the id of the object you want to update.

**Returns**

The object you just updated.


#Editor
The editor is an embedable tool, for creating and updating objects in a collection

## Install
To create an editor on a page use the following code

	<div id="cms-editor"></div>
	<script type="text/javascript" src="dist/js/todaycms.js"></script>
	<script type='text/javascript'>
		cms_setup({
			apikey: '7DdPrtGp9ZhKmk',
			collection: "listings"
		});
	</script>

***Required***: 'apikey' and 'collection' are required parameters for the editor to load.

## Setup

There are several customizations that can be made to the 'cms_setup' function.

###id
Used to update an existing record.

	<script type='text/javascript'>
		cms_setup({
			apikey: '7DdPrtGp9ZhKmk',
			collection: "listings",
			id: 12345
		});
	</script>

###config
Overrides the collections configuration for only this editor.

In this example we are changing the visibility of the account field:

	<script type='text/javascript'>
		cms_setup({
			apikey: '7DdPrtGp9ZhKmk',
			collection: "listings",
			config: {
				fields: {
					account: {
						hidden:true
					}
				}
			}
		});
	</script>

###data
Overrides the data for this object

	<script type='text/javascript'>
		cms_setup({
			apikey: '7DdPrtGp9ZhKmk',
			collection: "listings",
			data: {
				account: 12345
			}
		});
	</script>

###before_save
Function that is called before the object is saved to the api. It recieves a complete json copy of the object. Use this to make any modifications to the object before it is saved. If you return false, the save will be canceled, this is usefully for valdation errors.

	<script type='text/javascript'>
		cms_setup({
			apikey: '7DdPrtGp9ZhKmk',
			collection: "listings",
			before_save: function(object) {
				if (object.first_name === '') {
					alert('Please enter a first name.');
					return false;
				} else {
					return object;
				}
			}
		});
	</script>

###after_save
Function that is called after the object has been saved. It recieves a complete json copy of the object that was just saved to the database.

	<script type='text/javascript'>
		cms_setup({
			apikey: '7DdPrtGp9ZhKmk',
			collection: "listings",
			after_save: function(object) {
				window.location = "/account/listings/" + object.id;
			}
		});
	</script>

###bootstrap
Enable or disable the bootstrap css. Useful if bootstrap has already been loaded on the page.

	<script type='text/javascript'>
		cms_setup({
			apikey: '7DdPrtGp9ZhKmk',
			collection: "listings",
			bootstrap: false
		});
	</script>

###theme
Sets the theme. Availible themes: light or dark. The light theme is the default. Use false to disable the theme css.

	<script type='text/javascript'>
		cms_setup({
			apikey: '7DdPrtGp9ZhKmk',
			collection: "listings",
			theme: false,
			// or
			// theme: 'dark'
		});
	</script>
