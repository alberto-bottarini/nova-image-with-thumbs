# Laravel Nova Image With Thumbs Field

This custom fields add thumbnail ability to standard Image Field in Laravel Nova.

Take a look at this example:

```php
NovaImageWithThumbs::make('Image', 'image')
    ->thumbs([
        ['name' => 'thumbnail', 'w' => 200, 'h' => 100, 'method' => 'fit'],
    ])
    ->disk('public')
    ->path('images-from-nova')
    ->prunable()
    ->hideFromIndex(),
```

This field will automatically generate two images on your disk and will fill `image` and `thumbnail` attribute of your model with path of these files. 
Using `$model->image` you will find original uploaded image and with `$model->thumbnail` you will find a 200x100 image.

## Configuration

`thumbs` method accepts a list of associatable-array with these keys:

| Key    | Mandatory | Acceptance           | Description                                                    |
|--------|-----------|----------------------|----------------------------------------------------------------|
| name   | Y         | string               | The model column where you want to persist your thumbnail path |
| w      | Y         | integer              | The width of thumbnail                                         |
| h      | Y         | integer              | The height of thumbnail                                        |
| method | Y         | string (fit|resize)  | The Intervention method to generate image                      |

## Prunable

NovaImageWithThumbs takes care of prunable images. If you delete a model with thumbnails, the field will automatically deletes useless files for you.

## Show thumbnails to users

If you need to show thumbnails inside index or detail page, you can add a new standard Image Field to your resource:

```php
Image::make('Thumbnail', 'thumbnail')
    ->disk('public')
    ->path('images-from-nova')
    ->exceptOnForms()
```
