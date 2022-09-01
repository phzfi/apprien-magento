# Apprien Magento Extension

An extension for magento that uses apprien automatic pricing API
to automatically adjust prices and push price updates to apprien.

## Installation

TODO

## Usage

Settings are found in magento admin dashboard under Stores -> Configuration -> Apprien -> Apprien automatic pricing.
In the Authentication section, set your client ID and secret and save settings. If the label says
"Correct credentials entered." you have connected to apprien API successfully. After that select a company
in the Provider section and save settings again.

Now, whenever you buy something from magento store the extension updates pricing data in apprien.

As of now, changing prices in magento dynamically is TODO.