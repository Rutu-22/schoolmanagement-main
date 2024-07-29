// address.component.ts
import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { AddressService } from './address.service';

@Component({
  selector: 'app-address',
  templateUrl: './address.component.html',
  styleUrls: ['./address.component.scss']
})
export class AddressComponent implements OnInit {
  addressForm: FormGroup;
  addresses: any[] = [];
  selectedAddress: any = null;
  constructor(private formBuilder: FormBuilder, private addressService: AddressService) {}

  ngOnInit() {
    this.initForm();
    this.fetchAddresses();
  }

  initForm() {
    this.addressForm = this.formBuilder.group({
      street: ['', Validators.required],
      city: ['', Validators.required],
      state: ['', Validators.required],
      postal_code: ['', Validators.required]
    });
  }

  fetchAddresses() {
    this.addressService.getAddresses().subscribe(
      (response: any) => {
        this.addresses = response.addresses;
      },
      error => {
        console.error('Error fetching addresses:', error);
      }
    );
  }

  submitAddress() {
    if (this.addressForm.valid) {
      if (this.selectedAddress) {
        // If an address is selected, update it
        this.addressService.updateAddress(this.selectedAddress.id, this.addressForm.value).subscribe(
          (response: any) => {
            // Handle success
            this.fetchAddresses();
            this.addressForm.reset();
            this.selectedAddress = null; // Reset selected address
          },
          error => {
            console.error('Error updating address:', error);
          }
        );
      } else {
        // Otherwise, add a new address
        this.addressService.addAddress(this.addressForm.value).subscribe(
          (response: any) => {
            // Handle success
            this.fetchAddresses();
            this.addressForm.reset();
          },
          error => {
            console.error('Error adding address:', error);
          }
        );
      }
    }
  }
  editAddress(address: any) {
    this.selectedAddress = address;
    this.addressForm.patchValue({
      street: address.street,
      city: address.city,
      state: address.state,
      postal_code: address.postal_code
    });
  }

  deleteAddress(address: any) {
    if (confirm('Are you sure you want to delete this address?')) {
      this.addressService.deleteAddress(address.id).subscribe(
        (response: any) => {
          // Handle success
          this.fetchAddresses();
        },
        error => {
          console.error('Error deleting address:', error);
        }
      );
    }
  }
}
