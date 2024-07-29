// division.component.ts
import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { DivisionService } from './division.service';

@Component({
  selector: 'app-division',
  templateUrl: './division.component.html',
  styleUrls: ['./division.component.scss']
})
export class DivisionComponent implements OnInit {
  divisionForm: FormGroup;
  divisions: any[] = [];
  selectedDivision: any = null; // Track the selected division for updating

  constructor(private formBuilder: FormBuilder, private divisionService: DivisionService) {}

  ngOnInit() {
    this.initForm();
    this.fetchDivisions();
  }

  initForm() {
    this.divisionForm = this.formBuilder.group({
      name: ['', Validators.required],
      description: ['']
    });
  }

  fetchDivisions() {
    this.divisionService.getDivisions().subscribe(
      (response: any) => {
        this.divisions = response.divisions;
      },
      error => {
        console.error('Error fetching divisions:', error);
      }
    );
  }

  submitDivision() {
    if (this.divisionForm.valid) {
      if (this.selectedDivision) {
        // If a division is selected, update it
        this.divisionService.updateDivision(this.selectedDivision.id, this.divisionForm.value).subscribe(
          (response: any) => {
            // Handle success
            this.fetchDivisions();
            this.divisionForm.reset();
            this.selectedDivision = null; // Reset selected division
          },
          error => {
            console.error('Error updating division:', error);
          }
        );
      } else {
        // Otherwise, add a new division
        this.divisionService.addDivision(this.divisionForm.value).subscribe(
          (response: any) => {
            // Handle success
            this.fetchDivisions();
            this.divisionForm.reset();
          },
          error => {
            console.error('Error adding division:', error);
          }
        );
      }
    }
  }

  editDivision(division: any) {
    this.selectedDivision = division;
    this.divisionForm.patchValue({
      name: division.name,
      description: division.description
    });
  }

  deleteDivision(division: any) {
    if (confirm('Are you sure you want to delete this division?')) {
      this.divisionService.deleteDivision(division.id).subscribe(
        (response: any) => {
          // Handle success
          this.fetchDivisions();
        },
        error => {
          console.error('Error deleting division:', error);
        }
      );
    }
  }
}
