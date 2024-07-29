import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { CasteService } from './caste.service';

@Component({
  selector: 'app-caste',
  templateUrl: './caste.component.html',
  styleUrls: ['./caste.component.scss']
})
export class CasteComponent implements OnInit {
  casteForm: FormGroup;
  castes: any[] = [];
  selectedCaste: any = null;
  constructor(private formBuilder: FormBuilder, private casteservice: CasteService) {}

  ngOnInit() {
    this.initForm();
    this.fetchCastes();
  }

  initForm() {
    this.casteForm = this.formBuilder.group({
      name: ['', Validators.required],
    });
  }

  fetchCastes() {
    this.casteservice.getCastes().subscribe(
      (response: any) => {
        this.castes = response.castes;
      },
      error => {
        console.error('Error fetching castes:', error);
      }
    );
  }

  submitCaste() {
    if (this.casteForm.valid) {
      if (this.selectedCaste) {
        // If a caste is selected, update it
        this.casteservice.updateCaste(this.selectedCaste.id, this.casteForm.value).subscribe(
          (response: any) => {
            // Handle success
            this.fetchCastes();
            this.casteForm.reset();
            this.selectedCaste = null; // Reset selected caste
          },
          error => {
            console.error('Error updating caste:', error);
          }
        );
      } else {
        // Otherwise, add a new caste
        this.casteservice.addCaste(this.casteForm.value).subscribe(
          (response: any) => {
            // Handle success
            this.fetchCastes();
            this.casteForm.reset();
          },
          error => {
            console.error('Error adding caste:', error);
          }
        );
      }
    }
  }
  editCaste(caste: any) {
    this.selectedCaste = caste;
    this.casteForm.patchValue({
      name: caste.name,
      description: caste.description
    });
  }

  deleteCaste(caste: any) {
    if (confirm('Are you sure you want to delete this caste?')) {
      this.casteservice.deleteCaste(caste.id).subscribe(
        (response: any) => {
          // Handle success
          this.fetchCastes();
        },
        error => {
          console.error('Error deleting caste:', error);
        }
      );
    }
  }
}
