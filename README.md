# TodayCMS Documentation

<!-- ****************************************************************** -->

## API
The api is the server endpoint for creating, reading, updating and deleting objects from collections in the database.

### Authentication

All calls to the api must include a _token parameter to validate the call.

	http://todaycms-api.herokuapp.com/?_token=XXXXXXXXXXXX

This guide will not include the _token in the sample calls. It is implied that it must be included.

### Example Config
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

### Create

To create an object in a collection, issue a HTTP POST call with the object in the body of the POST.

	POST /collections/:collection

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

### Read

To read from a collection, issue a HTTP GET call

	GET /collections/:collection


**URL Parameters**

`:collection` is the key of the collection in the config.

**Returns**

An array of objects from the collection.

### Filtering

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

### Update

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

<!-- ****************************************************************** -->

## Config
The config is the definition of your collection in json.

### Parent
A config object that contains other objects (admin sections) and displays them in a group.

Parent elements are characterized by a subhead in the admin panel navigation. They also group elements in the [[Outline (call)]].


    "sample_parent": {
        "title": "Sample Parent",
        "type": "parent"
    }

**Parameters**

Name | Default | Description
------------- | ------------- | ------------- |
title | required | Descriptive title of the object. This value will be used to identify the object in the admin panel sidebar navigation. Required field.
type | parent | Object identifier. Required, must be set to: 'parent'

### Single
The Single object is created in the config.json file. This will create a single page that can be edited in the admin panel and displayed on the front end.

    "single-name": {
		"title": "single-title",
		"parent": "sample_parent",
		"type": "single",
		"fields": {
			...
		}
	}

**Parameters**

Name | Default | Description
------------- | ------------- | ------------- |
title | required | Descriptive title of the page. This value will be used to identify the object in the admin panel sidebar navigation. Required field.
type | single | Object identifier. Required, must be set to: 'single.'
parent | - | Defines parent container object. Allows you to group multiple objects together in the admin panel (like a folder).
nav | true | Allows you to hide or show object elements in the admin panel.


### Multiple
The Multiple object is created in the 'config.json' file. This will create a group of many pages (records) that can be edited in the admin panel and displayed on the front end.

    "multiple-name": {
		"title": "multiple-title",
		"parent": "sample_parent",
		"type": "multiple",
		"fields": {
			...
		}
	}

**Parameters**

Name | Default | Options | Description
------------- | ------------- | ------------- | ------------- |
title | required | | Descriptive title of the object. This value will be used to identify the object in the admin panel sidebar navigation. Required field.
type | multiple | | Object identifier. Required, must be set to: 'multiple.'
parent | '' | | Defines parent container object. Allows you to group multiple objects together in the admin panel (like a folder).
nav | true | true,false | Allows you to hide or show object elements in the admin panel.
filters  | '' | | Creates a select menu in the admin panel that will filter returned records.
sort  | '' | asc, desc | Sorts records on call.
display  | '' | | Defines values to display in the admin output table for each record.
download | true | true,false | Enables raw data download from admin panel. (csv file)
redirects | true | true,false | Enables option to create link (url) based records.
publish | true | true,false | Removes ability to save individual record as draft.

#### Filters Example

    "filters": ["value"],

#### Sort Example

    "sort":{
        "value":"asc"
    }

#### Display Example

    "display":["fname", "lname", "phone", "email"]

<!-- ****************************************************************** -->

## Config Fields

### Colorpicker
Creates a color picker field that will return a hex value through the API.

![TodayCMScolor picker](http://space.todaymade.com/todaycms/colorpicker2.jpg)

    "color": {
        "title": "Color Picker",
        "type": "colorpicker"
    }

### Date
Creates a single date field with a visual calendar drop down.

![Todaycms date field](http://space.todaymade.com/todaycms/date.jpg)

    "date": {
        "title": "Date Field",
        "type": "date"
    }

### Datetime
Creates a single date and time field with a visual drop down for each.

![todaycms date and time field](http://space.todaymade.com/todaycms/datetime.jpg)

    "datetime": {
        "title": "Date Time Field",
        "type": "datetime"
    }

Returns a single date/time value.

    [datetime] => 01/16/2012 12:00 am

### File
Creates a single file upload field.

![TodayCMS File](http://space.todaymade.com/todaycms/file.jpg)

    "file": {
        "title": "File Field",
        "type": "file"
    }

Returns an array.

    [file] => Array
        (
            [caption] =>
            [date] => 11/1/2012
            [url] => http://todaycms.s3.amazonaws.com/...filename.pdf
            [name] => filename.pdf
        )

### Files
Creates multiple file upload fields with sort and caption options.

![todaycms files](http://space.todaymade.com/todaycms/files.jpg)

    "files": {
        "title": "Files Field",
        "type": "files"
    }

Returns a multidimensional array.

    [files] => Array
        (
            [0] => Array
                (
                    [caption] => Pre-Order Form
                    [date] => 2/13/2012
                    [url] => http://todaycms.s3.amazonaws.com/...filename.pdf
                    [name] => Author Visit - Beth McKinney.pdf
                )
        )

### Form
Creates an instance of the TodayCMS FormBuilder. This will allow end users to create complex forms with a variety of options for data collection and submission.

![TodayCMS Formbuilder](http://space.todaymade.com/todaycms/formbuilder.jpg)

    "form": {
        "title": "Form Builder",
        "type": "formbuilder"
    }

**Available Fields**

* Single Line Text
* Multiple Line Text
* Dropdown Select
* Checkbox Options
* Multiple Choice (Radio Buttons)
* Phone
* Email

**Form Actions**

* Redirect to another page
* Display a message
* Send to Paypal

**Returns**

See the [[Formbuilder (helper)]]

### Hidden
Creates a hidden form field.

    "hidden": {
        "title": "Hidden Field",
        "type": "hidden",
    }

    "hidden-forced-value": {
        "title": "Hidden Forced Value Field",
        "type": "hidden",
        "value": "Forced Value"
    }

    "hidden-default-value": {
        "title": "Hidden Default Value Field",
        "type": "hidden",
        "default": "Default Value"
    }

    "hidden-timestamp-value": {
        "title": "Hidden Timestamp Value Field",
        "type": "hidden",
        "default": "timestamp"
    }

    "hidden-display-value": {
        "title": "Hidden Display Field",
        "type": "hidden",
        "value": "You should see this",
        "display": true
    }


**Parameters**

Name | Default | Options | Description
------------- | ------------- | ------------- | ------------- |
default | '' | | string, timestamp | Sets a default value for the hidden field. Timestamp will display current date/time.
display | false | true,false | Can see the value but can't change it
auto_increment | false | true, false | Creates unique id starting with 1.

### Image
Creates a single image upload field.

![image](http://space.todaymade.com/todaycms/image-field.jpg)

    "image": {
        "title": "Image Field",
        "type": "image",
        "sizes": {
            "thumb": {
                "height": 150,
                "width": 150,
                "resize_strategy": "crop"
            },
            "large": {
                "height": 500,
                "width": 500,
                "resize_strategy": "fit"
            }
        }
    }

Image fields allows you to determine multiple thumbnail/image sizes and cropping techniques.

**Parameters**

Name | Type | Default | Description
------------- | ------------- | ------------- | ------------- |
width | 1-5000 | Width of the input image | Width of the new image, in pixels
height | 1-5000 | Height of the input image | Height of the new image, in pixels
strip | boolean | false | Strips all metadata from the image. This is useful to keep thumbnails as small as possible.
flatten | boolean | true | Flattens all layers onto the specified background to achive better results from transparent formats to non-transparent formats, as explained in the [ImageMagick](http://www.imagemagick.org/script/command-line-options.php?#layers) documentation. **Important:** To preserve animations, GIF files are not flattened when this is set to true. To flatten GIF animations, use the frame parameter.
correct_gamma | boolean | false | Prevents gamma errors [common in many image scaling algorithms](http://www.4p8.com/eric.brasseur/gamma.html).
quality | 1-100 | Quality of the input image, or 92 | Controls the image compression for JPG and PNG images.
background | string | "#FFFFFF" | Either the hexadecimal code or [name](http://www.imagemagick.org/script/color.php#color_names) of the color used to fill the background (only used for the pad resize strategy). **Important:** By default, the background of transparent images is changed to white. For details about how to preserve transparency across all image types, see [this demo](https://transloadit.com/demos/image-resize/properly-preserve-transparency-across-all-image-types).
resize_strategy | string | "fit" | See [[Resize Strategies Table]]
zoom | boolean | true | If this is set to false, smaller images will not be stretched to the desired width and height. For details about the impact of zooming for your preferred resize strategy, see the [[Resize Strategies Table]].
crop | { x1: integer, y1: integer, x2: integer, y2: integer } | {} | Specify an object containing coordinates for the top left and bottom right corners of the rectangle to be cropped from the original image(s). For example, `{x1: 80, y1: 100, x2: 160, y2: 180}` will crop the area from `(80,100)` to `(160,180)` which is a square whose width and height are 80px. If crop is set, the width and height parameters are ignored, and the resize_strategy is set to crop automatically.
format | string | Format of the input image | The available formats are `"jpg"`, `"png"`, `"gif"`, and `"tiff"`.
gravity | string | center | The direction from which the image is to be cropped. The available options are `"center"`, `"top"`, `"bottom"`, `"left"`, and `"right"`. You can also combine options with a hyphen, such as `"bottom-right"`.
frame | integer | null (all frames) | Use this parameter when dealing with animated GIF files to specify which frame of the GIF is used for the operation. Specify 1 to use the first frame, 2 to use the second, and so on.
colorspace | string | " " | Sets the image colorspace. For details about the available values, see the [ImageMagick documentation](http://www.imagemagick.org/script/command-line-options.php#colorspace).
rotation | string / boolean / integer | true | Determines whether the image should be rotated. Set this to true to auto-rotate images that are misrotated, or depend on EXIF rotation settings. You can also set this to an integer to specify the rotation in degrees. You can also specify degrees> to rotate only when the image width exceeds the height (or degrees< if the width must be less than the height). Specify false to disable auto-fixing of misrotated images.
compress | string | null | Specifies pixel compression for when the image is written. Valid values are None, `"BZip"`, `"Fax"`, `"Group4"`, `"JPEG"`, `"JPEG2000"`, `"Lossless"`, `"LZW"`, `"RLE"`, and `"Zip"`. Compression is disabled by default.
blur | string | null | Specifies gaussian blur, using a value with the form `{radius}x{sigma}`. The radius value specifies the size of area the operator should look at when spreading pixels, and should typically be either `"0"` or at least two times the sigma value. The sigma value is an approximation of how many pixels the image is "spread"; think of it as the size of the brush used to blur the image. This number is a floating point value, enabling small values like `"0.5"` to be used. For details about how the radius and sigma values affect blurring, see [this example](http://www.imagemagick.org/Usage/blur/blur_montage.jpg).

**Watermark Parameters**

Name | Type | Default | Description
------------- | ------------- | ------------- | -------------
watermark_url | string | " " | A url indicating a PNG image to be overlaid above this image.
watermark_position | string/array | "center" | The position at which the watermark is placed. The available options are `"center"`, `"top"`, `"bottom"`, `"left"`, and `"right"`. You can also combine options, such as `"bottom-right"`. An array of possible values can also be specified, in which case one value will be selected at random, such as `["center","left","bottom-left","bottom-right"]`. _Note that this setting puts the watermark in the specified corner. To use a specific pixel offset for the watermark, you will need to add the padding to the image itself._
watermark_size | string | "" | The size of the watermark, as a percentage. For example, a value of `"50%"` means that size of the watermark will be 50% of the size of image on which it is placed.
watermark_resize_strategy | string | "fit" | Available values are `"fit"` and `"stretch"`.

### Images
Creates a multi image upload field. Useful for a gallery or rotating image.

![images](http://space.todaymade.com/todaycms/images.jpeg)

    "images": {
        "title": "Images Field",
        "type": "images",
        "sizes": {
            "thumb": {
                "height": 150,
                "width": 150,
                "resize_strategy": "crop"
            },
            "large": {
                "height": 500,
                "width": 500,
                "resize_strategy": "crop"
            }
        }
    }

See [parameters table](https://github.com/justinwalsh/todaycms/wiki/Image).

See [resize strategies table](https://github.com/justinwalsh/todaycms/wiki/Resize-Strategies-Table).

### Markitup
Creates an instance of the markItUp! universal markup editor. Editor allows easy access to HTML editing.

![MarkItUp! editor](http://space.todaymade.com/todaycms/markitup.jpeg)

    "markitup": {
        "title":"Markitup",
        "type":"markitup"
    }

### Multi
Creates a repeatable object that can utilize all TodayCMS field types.

![Multi Field](http://space.todaymade.com/todaycms/multi.jpeg)

    "multi": {
        "title": "Multi Field",
        "display": "title",
        "type": "multi",
            "fields": {
                ...
            }
        }
    }

**Parameters**

Name | Default | Options | Description
------------- | ------------- | ------------- | ------------- |
display | '' | | Field name to use as the identifier in the title cell.

### Multiselect
Creates a form field type that allows the selection of one or more items from a dropdown list.

![multi select static](http://space.todaymade.com/todaycms/multi-select-static.jpg)

### Static Options
Displays a static list of options.

    "multiselect": {
        "title": "Multi Select - Static",
        "type": "multiselect",
        "options": {
            "value": "Display Name",
            "value2": "Display Name 2"
        }
    }

### Reference Options
Displays the records from another object in the config. This is commonly used to create multiple relational data associations.

    "referenced-object": {
        "title": "Referenced Object",
        "type": "multiple",
        "fields": {
            "name": {
	        "title": "Record Name",
	        "type": "text"
            }
        }
    }

    "multiselect": {
        "title": "Multi Select - Reference",
        "type": "multiselect",
        "options": "referenced-object",
        "display": "name"
    }

**Parameters**

Name | Default | Options | Description
------------- | ------------- | ------------- | ------------- |
options | '' |  | A list of values (object), or reference name for another object in the config.
display | '' | | Field name to use as the identifier in the dropdown select

See also [[Select]].

### Password
Creates a password form field.

![password](http://space.todaymade.com/todaycms/password.jpg)

    "password": {
        "title": "Password Field",
        "type": "password"
    }

### Select
Creates a form field type that allows the selection of a single item from a dropdown list.

![static field](http://space.todaymade.com/todaycms/select.jpg)

### Static Options
Displays a static list of options.

    "select": {
        "title": "Select Field - Static Options",
        "type": "select",
        "options": {
            "value1": "Display Name One",
            "value2": "Display Name Two",
            "value3": "Display Name Three"
        }
    }

### Reference Options
Displays a list of the records from another object in the config. This is commonly used to create relational data association. For example, a list of categories or a grouping.

    "referenced-object": {
        "title": "Referenced Object",
        "type": "multiple",
        "fields": {
            "name": {
	        "title": "Record Name",
	        "type": "text"
            }
        }
    }

    "select-reference": {
        "title": "Select Field - Reference",
        "type": "select",
        "options": "referenced-object",
        "display": "name"
    }

**Parameters**

Name | Default | Options | Description
------------- | ------------- | ------------- | ------------- |
options | '' |  | A list of values (object), or reference name for another object in the config.
display | '' | | Field name to use as the identifier in the dropdown select
blank | '' | string | String uses for the default dropdown select option. Value is set to null.
hidden | false | true, false | Hides the select menu and passes a value through as a hidden field.
value | | | Sets a default value.

See also [[Multiselect]].

### Text
Creates a text form field.

![text ](http://space.todaymade.com/todaycms/text.jpg)

    "text": {
        "title": "Text Field",
        "type": "text"
    }

### Textarea
Creates a textarea form field.

![Textarea](http://space.todaymade.com/todaycms/textarea.jpg)

    "textarea": {
        "title": "Wysiwyg Field",
        "type": "textarea"
    }

### Wysiwyg
Creates a WYSIWYG textarea form field.

![WYSIWYG](http://space.todaymade.com/todaycms/wysiwyg.jpg)

    "wysiwyg": {
        "title": "Wysiwyg Field",
        "type": "wysiwyg"
    }


<!-- ****************************************************************** -->

##Editor
The editor is an embedable tool, for creating and updating objects in a collection

### Install
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

### Setup

There are several customizations that can be made to the 'cms_setup' function.

####id
Used to update an existing record.

	<script type='text/javascript'>
		cms_setup({
			apikey: '7DdPrtGp9ZhKmk',
			collection: "listings",
			id: 12345
		});
	</script>

####config
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

####data
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

####before_save
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

####after_save
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

####bootstrap
Enable or disable the bootstrap css. Useful if bootstrap has already been loaded on the page.

	<script type='text/javascript'>
		cms_setup({
			apikey: '7DdPrtGp9ZhKmk',
			collection: "listings",
			bootstrap: false
		});
	</script>

####theme
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

<!-- ****************************************************************** -->

## PHP SDK
The PHP SDK is a file called `connector.php` that makes it easier to work with the API. It contains helpers and functions that map to API calls.