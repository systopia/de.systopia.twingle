# Twingle Shop integration

With the Twingle Shop integration, products ordered through a Twingle Shop are
recorded as line items of the contribution created for the order. Any amount
exceeding the ordered products is recorded as a separate top up donation line
item.

## Enabling the Shop integration

1. In CiviCRM, go to **Administer** and choose **Twingle API configuration**.
2. Click on **Configure extension settings**.
3. Check **Use Twingle Shop Integration** and enter your Twingle API
   **Access Key**. The access key is required to retrieve the products from
   Twingle.
4. Save the settings.

## Configuring the Twingle profile

In each Twingle profile which is used for a Twingle Shop, the following options
are available in the **Shop Integration** section:

**Enable Shop Integration**
:   Process orders from Twingle Shop for this profile. The ordered products will
    appear as line items in the contribution.

**Default Financial Type**
:   The financial type used for line items of products which have not been
    mapped as price fields (see below).

**Financial Type for top up donations**
:   The financial type used for the donation part of an order. Donations are not
    subject to VAT, so this financial type should not have a sales tax account.

**Map Products as Price Fields**
:   Retrieve all products of the shops matching the project IDs of this profile
    from Twingle. Each shop is mapped as a price set, each product as a price
    field. You can select a financial type for each product and create, update
    or delete its price field.

    The price sets can also be used to manually record orders, e.g. phone
    orders, with the same line items as orders placed through the Twingle Shop.

Products which have changed in Twingle since their price field has been created,
or whose price field amount does not match the price in Twingle, are marked as
outdated and can be updated with the **Update** button.

## VAT

In the TwingleMANAGER, a VAT rate can be specified for each product. Twingle
always submits the amounts **including** VAT, but does not submit the VAT rate
along with an order.

CiviCRM, however, handles VAT through the financial type of a line item: if the
financial type has a sales tax account, CiviCRM adds the tax rate of this
account **on top** of the line total.

To bring both together, the Twingle extension

* creates the price fields of products with the **net price**, i.e. the price
  from Twingle without the VAT rate specified in the TwingleMANAGER. The net
  price is not rounded to cents (CiviCRM stores price field amounts with nine
  decimals), so that CiviCRM calculates the same gross amounts as Twingle when
  recording orders manually. Products without VAT are created with their full
  price.
* splits off the VAT included in the submitted amounts when processing an
  order, using the tax rate of the line item's financial type. Line total and
  tax amount of each line item add up to the amount submitted by Twingle again.
  The VAT is recorded on the sales tax account of the financial type.

The VAT is calculated on the line total, i.e. on the total amount of all units
of a product. As the net unit price is rounded, the net unit price multiplied
by the quantity may differ from the net line total by a cent.

Example: a coffee costs 3.00 EUR including 7 % VAT. The price field is created
with 2.803738318 EUR (3.00 EUR / 1.07). When an order for two coffees
(6.00 EUR) is submitted, the line item is created with a unit price of
2.80 EUR, a line total of 5.61 EUR and a tax amount of 0.39 EUR, adding up to
6.00 EUR again. An order for two coffees recorded manually in CiviCRM results
in the same amounts.

### Configuring VAT in CiviCRM

For each financial type used for products with VAT, you have to configure the
same VAT rate as in the TwingleMANAGER:

1. Go to **Administer** › **CiviContribute** › **Financial Accounts** and
   create a financial account for the VAT rate (e.g. "VAT 7 %"). Set the
   financial account type to **Liability**, check **Is Tax?** and enter the
   **Tax Rate** in percent (e.g. `7`).
2. Go to **Administer** › **CiviContribute** › **Financial Types**, click on
   **Accounts** for the financial type of the products and assign the financial
   account created above with the relationship **Sales Tax Account is**.
3. Select this financial type for the products in the Twingle profile (see
   above) and create or update their price fields.

If you want VAT to be shown on contribution pages and invoices, also enable
**Tax and Invoicing** in **Administer** › **CiviContribute** ›
**CiviContribute Component Settings**.

!!! warning
    If the VAT rate of a product in the TwingleMANAGER differs from the tax rate
    of its financial type in CiviCRM, the tax rate of the financial type is used
    and a warning is logged. Without a sales tax account, the full amount is
    recorded as revenue and no VAT is recorded.

### Products created before VAT support

Price fields of products created with an earlier version of this extension
contain the price **including** VAT. These products are marked as outdated and
CiviCRM's system status shows a warning until they have been updated. To update
them, open the Twingle profiles using these shops and click on **Update** for
each product.

If the VAT feature is not yet available for your Twingle account, Twingle does
not provide a VAT rate for the products. These products are treated as products
without VAT, i.e. their price fields contain the full price.
