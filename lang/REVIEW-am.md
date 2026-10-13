# Amharic review

TODO(review-am): every value in `am.json` is a draft written without a native
speaker's review, as is the Amharic demo copy in `database/seeders/DemoContentSeeder.php`.
Check tone (formal "እርስዎ" is used for the visitor), terminology for technical words
(ሲስተም / ሥርዓት, ዲዛይን, ብራንድ) and the validation messages before launch.

When a string is reviewed, nothing needs to change in code; edit `am.json` in place.
Keys must stay identical to `en.json` (tests/Feature/LocalizationTest.php checks this).
